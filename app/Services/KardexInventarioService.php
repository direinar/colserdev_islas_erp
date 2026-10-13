<?php

namespace App\Services;

use App\Models\Compra;
use App\Models\CompraLubricante;
use App\Models\InventarioSaldoInicial;
use App\Models\Turno;
use App\Models\TurnoLubricante;
use Illuminate\Support\Collection;

/**
 * Kardex de inventario al costo promedio. Solo lee el saldo inicial, compras y planillas de turno:
 * el inventario no se edita, todas las correcciones se hacen en su origen.
 */
class KardexInventarioService
{
    /**
     * Kardex ya calculados en esta petición. Varios informes piden los mismos inventarios
     * (control caja, cierre, anticipo, saldos); se calculan una sola vez. El servicio se
     * registra como "scoped" (AppServiceProvider), así que no sobrevive entre peticiones.
     *
     * @var array<string, mixed>
     */
    private array $memoria = [];

    /**
     * @return array{rows: list<array<string, mixed>>, totales: array<string, float>}
     */
    public function gasolina(): array
    {
        return $this->memoria['gasolina'] ??= $this->combustible(InventarioSaldoInicial::GASOLINA, 'gasolina', 'distribucion_gasolina', 'tirillas_galones_corriente', 'tirillas_valor_corriente', 'precio_corriente');
    }

    /**
     * @return array{rows: list<array<string, mixed>>, totales: array<string, float>}
     */
    public function acpm(): array
    {
        return $this->memoria['acpm'] ??= $this->combustible(InventarioSaldoInicial::ACPM, 'acpm', 'distribucion_acpm', 'tirillas_galones_acpm', 'tirillas_valor_acpm', 'precio_acpm');
    }

    /**
     * El kardex completo, recortado a un periodo: lo anterior a "desde" se resume en una
     * línea SALDO ANTERIOR (saldo y promedio a esa fecha) y los totales son los del periodo,
     * con el saldo al final del periodo.
     *
     * @param  array{rows: list<array<string, mixed>>, totales: array<string, float>}  $kardex
     * @return array{rows: list<array<string, mixed>>, totales: array<string, float>}
     */
    public function delPeriodo(array $kardex, string $desde, string $hasta): array
    {
        $fecha = fn (array $fila): string => $fila['fecha_compra'] ?? $fila['fecha_venta'];
        $anteriores = array_values(array_filter($kardex['rows'], fn (array $fila): bool => $fecha($fila) < $desde));
        $delPeriodo = array_values(array_filter($kardex['rows'], fn (array $fila): bool => $fecha($fila) >= $desde && $fecha($fila) <= $hasta));

        $ultimaAnterior = end($anteriores) ?: null;
        $saldoUnidades = (float) ($ultimaAnterior['saldo_unidades'] ?? 0.0);
        $saldoValor = (float) ($ultimaAnterior['valor_saldo'] ?? 0.0);

        $filas = $ultimaAnterior ? [[
            'tipo' => 'saldo_anterior',
            'numero_turno' => null,
            'fecha_venta' => null,
            'fecha_compra' => $desde,
            'compra_id' => null,
            'factura' => 'SALDO ANTERIOR',
            'referencia' => '',
            'entradas' => 0.0,
            'valor_entradas' => 0.0,
            'salidas' => 0.0,
            'valor_salidas' => 0.0,
            'valor_venta' => 0.0,
            'precio' => null,
            'saldo_unidades' => $saldoUnidades,
            'valor_saldo' => $saldoValor,
            'promedio' => $saldoUnidades > 0 ? $saldoValor / $saldoUnidades : 0.0,
        ]] : [];

        // Mismas claves y orden que los totales del kardex completo.
        $totales = array_fill_keys(array_keys($kardex['totales']), 0.0);
        foreach ($delPeriodo as $fila) {
            foreach (['entradas', 'salidas', 'valor_entradas', 'valor_salidas', 'valor_venta'] as $columna) {
                $totales[$columna] += (float) $fila[$columna];
            }
        }

        $ultima = end($delPeriodo) ?: null;
        $totales['saldo_unidades'] = (float) ($ultima['saldo_unidades'] ?? $saldoUnidades);
        $totales['valor_saldo'] = (float) ($ultima['valor_saldo'] ?? $saldoValor);

        return ['rows' => [...$filas, ...$delPeriodo], 'totales' => $totales];
    }

    /**
     * Saldos iniciales cargados, por inventario y referencia ("gasolina|", "canastilla|MOBIL ...").
     *
     * @return Collection<string, InventarioSaldoInicial>
     */
    private function saldosIniciales(): Collection
    {
        return $this->memoria['saldos_iniciales'] ??= InventarioSaldoInicial::query()
            ->get()
            ->keyBy(fn (InventarioSaldoInicial $saldo): string => $saldo->inventario.'|'.$saldo->referencia);
    }

    /**
     * Con saldo inicial, el kardex arranca en la fecha de corte: los movimientos anteriores
     * no cuentan y el saldo inicial es la primera línea.
     *
     * @param  Collection<int, array<string, mixed>>  $movimientos
     * @return Collection<int, array<string, mixed>>
     */
    private function conSaldoInicial(Collection $movimientos, ?InventarioSaldoInicial $saldo): Collection
    {
        if (! $saldo) {
            return $movimientos;
        }

        $corte = $saldo->fecha_corte->format('Y-m-d');
        $unidades = (float) $saldo->unidades;

        return collect([[
            'tipo' => 'saldo_inicial',
            'numero_turno' => null,
            'fecha_venta' => null,
            'fecha_compra' => $corte,
            'compra_id' => null,
            'factura' => 'SALDO INICIAL',
            'entradas' => $unidades,
            'valor_entradas' => round($unidades * (float) $saldo->costo_unitario, 2),
            'salidas' => 0.0,
            'valor_venta' => 0.0,
            'precio' => null,
            // "!" ordena antes que las compras ("0") y las ventas ("1") del mismo día.
            'orden' => $corte.'-!',
        ]])->concat($movimientos->filter(
            fn (array $movimiento): bool => ($movimiento['fecha_compra'] ?? $movimiento['fecha_venta']) >= $corte
        ));
    }

    /**
     * @return array{rows: list<array<string, mixed>>, totales: array<string, float>}
     */
    public function canastilla(string $producto): array
    {
        return $this->canastillaPorReferencia()[$producto] ?? $this->calcular(collect());
    }

    /**
     * Referencias de canastilla con movimientos (compras o ventas en planillas), ordenadas.
     *
     * @return list<string>
     */
    public function referenciasCanastilla(): array
    {
        return array_keys($this->canastillaPorReferencia());
    }

    /**
     * Kardex de cada referencia de canastilla, con todas las compras y ventas cargadas en dos
     * consultas (antes eran dos consultas por referencia, sobre todo el historial).
     *
     * @return array<string, array{rows: list<array<string, mixed>>, totales: array<string, float>}>
     */
    private function canastillaPorReferencia(): array
    {
        if (isset($this->memoria['canastilla'])) {
            return $this->memoria['canastilla'];
        }

        $entradas = CompraLubricante::query()
            ->get()
            ->groupBy(fn (CompraLubricante $compra): string => (string) $compra->nombre)
            ->map(fn (Collection $compras): Collection => $compras->map(fn (CompraLubricante $compra): array => $this->movimientoCompra(
                $compra->id,
                $compra->fecha->format('Y-m-d'),
                (string) $compra->no_fc,
                (float) $compra->unidades,
                (float) $compra->vr_sin_iva,
            )));

        // Una salida por planilla y referencia: se suman las líneas del mismo producto en el turno.
        $salidas = TurnoLubricante::query()
            ->whereHas('turno')
            ->with('turno')
            ->get()
            ->groupBy(fn (TurnoLubricante $linea): string => (string) $linea->producto)
            ->map(fn (Collection $lineas): Collection => $lineas
                ->groupBy('turno_id')
                ->map(function (Collection $delTurno): array {
                    /** @var Turno $turno */
                    $turno = $delTurno->first()->turno;
                    $cantidad = (float) $delTurno->sum('cantidad');
                    // La planilla guarda valor_sin_iva ya multiplicado por la cantidad de la línea.
                    $valorVenta = (float) $delTurno->sum(fn (TurnoLubricante $linea): float => (float) $linea->valor_sin_iva);

                    return $this->movimientoVenta(
                        $turno->numero_turno,
                        $turno->fecha->format('Y-m-d'),
                        $cantidad,
                        $valorVenta,
                        $cantidad > 0 ? $valorVenta / $cantidad : 0.0,
                    );
                })
                ->values());

        $saldos = $this->saldosIniciales()
            ->filter(fn (InventarioSaldoInicial $saldo): bool => $saldo->inventario === InventarioSaldoInicial::CANASTILLA)
            ->keyBy('referencia');

        $referencias = $entradas->keys()->concat($salidas->keys())->concat($saldos->keys())->filter()->unique()->sort()->values();

        return $this->memoria['canastilla'] = $referencias
            ->mapWithKeys(fn (string $referencia): array => [
                $referencia => $this->calcular($this->conSaldoInicial(
                    ($entradas->get($referencia) ?? collect())->concat($salidas->get($referencia) ?? collect()),
                    $saldos->get($referencia),
                )),
            ])
            ->all();
    }

    /**
     * Consolidado de canastilla: todos los movimientos de todas las referencias en una sola hoja.
     * Cada salida conserva el costo promedio de su propia referencia, así la suma de los totales
     * de cada referencia es igual a los totales del control.
     *
     * @return array{rows: list<array<string, mixed>>, totales: array<string, float>}
     */
    public function canastillaControl(): array
    {
        return $this->memoria['canastilla_control'] ??= $this->calcularCanastillaControl();
    }

    /**
     * @return array{rows: list<array<string, mixed>>, totales: array<string, float>}
     */
    private function calcularCanastillaControl(): array
    {
        $movimientos = collect($this->canastillaPorReferencia())->flatMap(
            fn (array $kardex, string $referencia): array => array_map(
                fn (array $row): array => $row + ['referencia' => $referencia],
                $kardex['rows'],
            )
        );

        $saldoUnidades = 0.0;
        $saldoValor = 0.0;

        $totales = [
            'entradas' => 0.0,
            'salidas' => 0.0,
            'saldo_unidades' => 0.0,
            'valor_entradas' => 0.0,
            'valor_salidas' => 0.0,
            'valor_saldo' => 0.0,
            'valor_venta' => 0.0,
        ];

        $rows = $movimientos
            ->sortBy(fn (array $row): string => ($row['fecha_compra'] ?? $row['fecha_venta'])
                .'-'.match ($row['tipo']) {
                    'saldo_inicial' => '!',
                    'compra' => '0',
                    default => '1',
                }
                .'-'.sprintf('%010d', $row['compra_id'] ?? $row['numero_turno'] ?? 0)
                .'-'.$row['referencia'])
            ->values()
            ->map(function (array $row) use (&$saldoUnidades, &$saldoValor, &$totales): array {
                $saldoUnidades += $row['entradas'] - $row['salidas'];
                $saldoValor += $row['valor_entradas'] - $row['valor_salidas'];

                foreach (['entradas', 'salidas', 'valor_entradas', 'valor_salidas', 'valor_venta'] as $columna) {
                    $totales[$columna] += $row[$columna];
                }

                return ['saldo_unidades' => $saldoUnidades, 'valor_saldo' => $saldoValor] + $row;
            })
            ->all();

        $totales['saldo_unidades'] = $saldoUnidades;
        $totales['valor_saldo'] = $saldoValor;

        return ['rows' => $rows, 'totales' => $totales];
    }

    /**
     * @return array{rows: list<array<string, mixed>>, totales: array<string, float>}
     */
    private function combustible(string $inventario, string $columnaGalones, string $columnaCosto, string $columnaSalidas, string $columnaVenta, string $columnaPrecio): array
    {
        $entradas = Compra::query()
            ->where(fn ($query) => $query->where($columnaGalones, '>', 0)->orWhere($columnaCosto, '>', 0))
            ->get()
            ->map(fn (Compra $compra): array => $this->movimientoCompra(
                $compra->id,
                $compra->fecha->format('Y-m-d'),
                (string) $compra->factura,
                (float) $compra->{$columnaGalones},
                (float) $compra->{$columnaCosto},
            ));

        $salidas = Turno::query()
            ->where(fn ($query) => $query->where($columnaSalidas, '>', 0)->orWhere($columnaVenta, '>', 0))
            ->get()
            ->map(fn (Turno $turno): array => $this->movimientoVenta(
                $turno->numero_turno,
                $turno->fecha->format('Y-m-d'),
                (float) $turno->{$columnaSalidas},
                (float) $turno->{$columnaVenta},
                (float) $turno->{$columnaPrecio},
            ));

        return $this->calcular($this->conSaldoInicial(
            $entradas->concat($salidas),
            $this->saldosIniciales()->get($inventario.'|'),
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function movimientoCompra(int $compraId, string $fecha, string $factura, float $unidades, float $costo): array
    {
        return [
            'tipo' => 'compra',
            'numero_turno' => null,
            'fecha_venta' => null,
            'fecha_compra' => $fecha,
            'compra_id' => $compraId,
            'factura' => $factura,
            'entradas' => $unidades,
            'valor_entradas' => $costo,
            'salidas' => 0.0,
            'valor_venta' => 0.0,
            'precio' => null,
            'orden' => $fecha.'-0-'.sprintf('%010d', $compraId),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function movimientoVenta(int $numeroTurno, string $fecha, float $unidades, float $valorVenta, float $precio): array
    {
        return [
            'tipo' => 'venta',
            'numero_turno' => $numeroTurno,
            'fecha_venta' => $fecha,
            'fecha_compra' => null,
            'compra_id' => null,
            'factura' => '',
            'entradas' => 0.0,
            'valor_entradas' => 0.0,
            'salidas' => $unidades,
            'valor_venta' => $valorVenta,
            'precio' => $precio,
            'orden' => $fecha.'-1-'.sprintf('%010d', $numeroTurno),
        ];
    }

    /**
     * Saldo = saldo anterior + entradas - salidas (en unidades y en valores).
     * Las salidas se valoran al promedio anterior y el promedio es saldo en valores / saldo en unidades.
     *
     * @param  Collection<int, array<string, mixed>>  $movimientos
     * @return array{rows: list<array<string, mixed>>, totales: array<string, float>}
     */
    private function calcular(Collection $movimientos): array
    {
        $saldoUnidades = 0.0;
        $saldoValor = 0.0;
        $promedio = 0.0;

        $totales = [
            'entradas' => 0.0,
            'salidas' => 0.0,
            'saldo_unidades' => 0.0,
            'valor_entradas' => 0.0,
            'valor_salidas' => 0.0,
            'valor_saldo' => 0.0,
            'valor_venta' => 0.0,
        ];

        $rows = $movimientos
            ->sortBy('orden')
            ->values()
            ->map(function (array $movimiento) use (&$saldoUnidades, &$saldoValor, &$promedio, &$totales): array {
                unset($movimiento['orden']);

                $valorSalidas = $movimiento['salidas'] * $promedio;
                $saldoUnidades += $movimiento['entradas'] - $movimiento['salidas'];
                $saldoValor += $movimiento['valor_entradas'] - $valorSalidas;
                $promedio = $saldoUnidades > 0 ? $saldoValor / $saldoUnidades : 0.0;

                $totales['entradas'] += $movimiento['entradas'];
                $totales['salidas'] += $movimiento['salidas'];
                $totales['valor_entradas'] += $movimiento['valor_entradas'];
                $totales['valor_salidas'] += $valorSalidas;
                $totales['valor_venta'] += $movimiento['valor_venta'];

                return $movimiento + [
                    'saldo_unidades' => $saldoUnidades,
                    'valor_salidas' => $valorSalidas,
                    'valor_saldo' => $saldoValor,
                    'promedio' => $promedio,
                ];
            })
            ->all();

        $totales['saldo_unidades'] = $saldoUnidades;
        $totales['valor_saldo'] = $saldoValor;

        return ['rows' => $rows, 'totales' => $totales];
    }
}
