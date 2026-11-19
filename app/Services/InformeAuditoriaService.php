<?php

namespace App\Services;

use App\Models\FuelPrice;
use App\Models\InventarioSaldoInicial;
use App\Models\LecturaElectronica;
use App\Models\TanqueSaldoInicial;
use App\Models\Turno;
use Illuminate\Support\Collection;

/**
 * Bloques del informe de auditoría (Auditoría del administrador) que cruzan módulos:
 * lectura electrónica contra cierres de IAPROPIADA, informe de tanques contra el inventario,
 * estado de las últimas planillas y diferencia de los comprobantes contables.
 */
class InformeAuditoriaService
{
    /**
     * Planillas que muestra el bloque ESTADO DE ÚLTIMAS PLANILLAS.
     */
    public const ULTIMAS_PLANILLAS = 10;

    /**
     * Combustible del informe de tanques => inventario del kardex.
     *
     * @var array<string, string>
     */
    private const INVENTARIOS = [
        TanqueSaldoInicial::CORRIENTE => InventarioSaldoInicial::GASOLINA,
        TanqueSaldoInicial::ACPM => InventarioSaldoInicial::ACPM,
    ];

    public function __construct(
        private ControlTanquesService $controlTanques,
        private KardexInventarioService $kardex,
        private ControlCajaService $controlCaja,
        private ComprobanteContableComprasService $comprobanteCompras,
        private RecaudosCarteraService $recaudos,
    ) {}

    /**
     * Venta del día según lectura electrónica contra la de los cierres de IAPROPIADA, en pesos,
     * para los días del periodo con lecturas o con planillas. La diferencia solo se calcula si
     * todas las mangueras tienen lectura final (igual que el módulo de lectura electrónica).
     *
     * @return array{
     *     filas: list<array{fecha: string, lectura: float|null, iapropiada: float, diferencia: float|null, completa: bool}>,
     *     totales: array{lectura: float, iapropiada: float, diferencia: float}
     * }
     */
    public function lecturas(string $desde, string $hasta): array
    {
        $lecturas = LecturaElectronica::query()
            ->whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hasta)
            ->get()
            ->groupBy(fn (LecturaElectronica $lectura): string => $lectura->fecha->format('Y-m-d'));

        $turnos = Turno::query()->noAnulados()
            ->whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hasta)
            ->orderBy('numero_turno')
            ->get()
            ->groupBy(fn (Turno $turno): string => $turno->fecha->format('Y-m-d'));

        $fechas = $lecturas->keys()->merge($turnos->keys())->unique()->sort()->values();
        $siguienteTurno = (int) Turno::query()->max('numero_turno') + 1;
        $totales = ['lectura' => 0.0, 'iapropiada' => 0.0, 'diferencia' => 0.0];
        $filas = [];

        foreach ($fechas as $fecha) {
            /** @var Collection<int, LecturaElectronica> $delDia */
            $delDia = $lecturas->get($fecha, collect());
            /** @var Collection<int, Turno> $turnosDelDia */
            $turnosDelDia = $turnos->get($fecha, collect());
            $ultimoTurno = $turnosDelDia->last();

            // Mismo precio que el módulo de lectura electrónica: el del último turno del día.
            $precioCorriente = (float) ($ultimoTurno?->precio_corriente ?: FuelPrice::precioParaTurno('Gasolina', $siguienteTurno));
            $precioAcpm = (float) ($ultimoTurno?->precio_acpm ?: FuelPrice::precioParaTurno('ACPM', $siguienteTurno));

            $completa = $delDia->whereNotNull('lectura_final')->pluck('manguera')->unique()->count() === count(LecturaElectronica::MANGUERAS);
            $lectura = $completa
                ? round((float) $delDia->where('combustible', 'corriente')->sum('galones') * $precioCorriente)
                    + round((float) $delDia->where('combustible', 'acpm')->sum('galones') * $precioAcpm)
                : null;
            $iapropiada = (float) $turnosDelDia->sum(fn (Turno $turno): float => (float) $turno->tirillas_valor_corriente + (float) $turno->tirillas_valor_acpm);
            $diferencia = $lectura === null ? null : $lectura - $iapropiada;

            if ($lectura !== null) {
                $totales['lectura'] += $lectura;
                $totales['iapropiada'] += $iapropiada;
                $totales['diferencia'] += $diferencia;
            }

            $filas[] = compact('fecha', 'lectura', 'iapropiada', 'diferencia', 'completa');
        }

        return ['filas' => $filas, 'totales' => $totales];
    }

    /**
     * Galones en tanque de cada día según el informe de control de tanques (saldo del
     * movimiento) contra el saldo en unidades del inventario (kardex), por combustible.
     *
     * @return list<array{fecha: string, corriente: array{tanques: float|null, inventario: float|null, diferencia: float|null}, acpm: array{tanques: float|null, inventario: float|null, diferencia: float|null}}>
     */
    public function inventarios(string $desde, string $hasta): array
    {
        $tanques = $this->controlTanques->informe($desde, $hasta)['filas'];
        $kardex = [
            TanqueSaldoInicial::CORRIENTE => $this->saldosPorFecha($this->kardex->gasolina()['rows']),
            TanqueSaldoInicial::ACPM => $this->saldosPorFecha($this->kardex->acpm()['rows']),
        ];

        return array_map(function (array $dia) use ($kardex): array {
            $fila = ['fecha' => $dia['fecha']];

            foreach (array_keys(self::INVENTARIOS) as $combustible) {
                $enTanques = $dia[$combustible]['saldo'];
                $enInventario = $this->saldoAl($kardex[$combustible], $dia['fecha']);

                $fila[$combustible] = [
                    'tanques' => $enTanques,
                    'inventario' => $enInventario,
                    'diferencia' => $enTanques !== null && $enInventario !== null ? round($enTanques - $enInventario, 3) : null,
                ];
            }

            return $fila;
        }, $tanques);
    }

    /**
     * Las últimas planillas del periodo con su cuadre y su estado de revisión (antes en el dashboard).
     *
     * @return Collection<int, Turno>
     */
    public function ultimasPlanillas(string $desde, string $hasta): Collection
    {
        return Turno::query()->noAnulados()
            ->whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hasta)
            ->withSum('lubricantes as canastilla_total', 'total')
            ->orderByDesc('numero_turno')
            ->limit(self::ULTIMAS_PLANILLAS)
            ->get();
    }

    /**
     * Diferencia (débitos − créditos) de cada comprobante contable del periodo; debe ser cero.
     *
     * @return list<array{nombre: string, ruta: string, diferencia: float}>
     */
    public function comprobantes(string $desde, string $hasta): array
    {
        $compras = $this->comprobanteCompras->generar($desde, $hasta)['totales'];

        return [
            [
                'nombre' => 'Comprobante contable de ventas',
                'ruta' => 'informes.comprobante-ventas.index',
                'diferencia' => round((float) $this->controlCaja->comprobanteVentas($desde, $hasta)['comprobante']['diferencia']),
            ],
            [
                'nombre' => 'Comprobante contable de compras',
                'ruta' => 'informes.comprobante-contable-compras.index',
                'diferencia' => round($compras['debito'] - $compras['credito']),
            ],
            [
                'nombre' => 'Comprobante contable de recaudos de cartera',
                'ruta' => 'informes.comprobante-recaudos.index',
                'diferencia' => round((float) $this->recaudos->comprobanteRecaudos($desde, $hasta)['comprobante']['diferencia']),
            ],
        ];
    }

    /**
     * Saldo en unidades al cierre de cada fecha con movimientos del kardex.
     *
     * @param  list<array<string, mixed>>  $filas
     * @return array<string, float>
     */
    private function saldosPorFecha(array $filas): array
    {
        $saldos = [];

        foreach ($filas as $fila) {
            $saldos[$fila['fecha_compra'] ?? $fila['fecha_venta']] = (float) $fila['saldo_unidades'];
        }

        ksort($saldos);

        return $saldos;
    }

    /**
     * Saldo del kardex al cierre del día: el del último día con movimientos hasta esa fecha;
     * sin movimientos anteriores aún no hay inventario (null).
     *
     * @param  array<string, float>  $saldos
     */
    private function saldoAl(array $saldos, string $fecha): ?float
    {
        $saldo = null;

        foreach ($saldos as $dia => $valor) {
            if ($dia > $fecha) {
                break;
            }
            $saldo = $valor;
        }

        return $saldo;
    }
}
