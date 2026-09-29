<?php

namespace App\Services;

use App\Models\Compra;
use App\Models\CompraLubricante;
use App\Models\Turno;
use App\Models\TurnoLubricante;
use Illuminate\Support\Collection;

/**
 * Kardex de inventario al costo promedio. Solo lee compras y planillas de turno:
 * el inventario no se edita, todas las correcciones se hacen en su origen.
 */
class KardexInventarioService
{
    /**
     * @return array{rows: list<array<string, mixed>>, totales: array<string, float>}
     */
    public function gasolina(): array
    {
        return $this->combustible('gasolina', 'distribucion_gasolina', 'lecturas_galones_corriente', 'tirillas_valor_corriente', 'precio_corriente');
    }

    /**
     * @return array{rows: list<array<string, mixed>>, totales: array<string, float>}
     */
    public function acpm(): array
    {
        return $this->combustible('acpm', 'distribucion_acpm', 'lecturas_galones_acpm', 'tirillas_valor_acpm', 'precio_acpm');
    }

    /**
     * @return array{rows: list<array<string, mixed>>, totales: array<string, float>}
     */
    public function canastilla(string $producto): array
    {
        $entradas = CompraLubricante::query()
            ->where('nombre', $producto)
            ->get()
            ->map(fn (CompraLubricante $compra): array => $this->movimientoCompra(
                $compra->id,
                $compra->fecha->format('Y-m-d'),
                (string) $compra->no_fc,
                (float) $compra->unidades,
                (float) $compra->vr_sin_iva,
            ));

        $salidas = Turno::query()
            ->whereHas('lubricantes', fn ($query) => $query->where('producto', $producto))
            ->with(['lubricantes' => fn ($query) => $query->where('producto', $producto)])
            ->get()
            ->map(function (Turno $turno): array {
                $cantidad = (float) $turno->lubricantes->sum('cantidad');
                $valorVenta = (float) $turno->lubricantes->sum(fn (TurnoLubricante $linea): float => (float) $linea->total);

                return $this->movimientoVenta(
                    $turno->numero_turno,
                    $turno->fecha->format('Y-m-d'),
                    $cantidad,
                    $valorVenta,
                    $cantidad > 0 ? $valorVenta / $cantidad : 0.0,
                );
            });

        return $this->calcular($entradas->concat($salidas));
    }

    /**
     * @return array{rows: list<array<string, mixed>>, totales: array<string, float>}
     */
    private function combustible(string $columnaGalones, string $columnaCosto, string $columnaSalidas, string $columnaVenta, string $columnaPrecio): array
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

        return $this->calcular($entradas->concat($salidas));
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
