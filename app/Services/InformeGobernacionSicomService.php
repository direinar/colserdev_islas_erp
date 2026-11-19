<?php

namespace App\Services;

use App\Models\Compra;
use App\Models\LecturaElectronica;
use Illuminate\Support\Carbon;

/**
 * Informe mensual para la Gobernación y el SICOM (plantilla "INFORME GOBERNACION Y SICOM"):
 *
 * 1. Lecturas electrónicas de cada manguera: inicial (final del último día del mes anterior),
 *    final (la del último día del mes) y la venta del mes (final − inicial) en PLUS o ACPM.
 * 2. Compras del mes, en galones, tomadas del módulo de compras.
 * 3. Resumen para la declaración del SICOM por producto, desde el inventario (kardex): saldo
 *    al inicio + compras − ventas ± ajustes = saldo al cierre, comparado con el saldo final del
 *    inventario. La diferencia debe ser cero.
 */
class InformeGobernacionSicomService
{
    public function __construct(private KardexInventarioService $kardex) {}

    /**
     * @param  string  $mes  Año y mes (Y-m).
     * @return array{
     *     mes: string,
     *     desde: string,
     *     hasta: string,
     *     lecturas: array{filas: list<array<string, mixed>>, totales: array{plus: float, acpm: float, total: float}, incompletas: list<string>},
     *     compras: array{filas: list<array{fecha: string, factura: string, plus: float, acpm: float, total: float}>, totales: array{plus: float, acpm: float, total: float}},
     *     sicom: array<string, array{saldo_inicio: float, compras: float, ventas: float, ajustes: float, saldo_cierre: float, saldo_inventario: float, diferencia: float}>
     * }
     */
    public function informe(string $mes): array
    {
        $inicio = Carbon::createFromFormat('Y-m-d', $mes.'-01')->startOfDay();
        $desde = $inicio->format('Y-m-d');
        $hasta = $inicio->copy()->endOfMonth()->format('Y-m-d');

        $sicom = [
            'corriente' => $this->resumenSicom($this->kardex->gasolina()['rows'], $desde, $hasta),
            'acpm' => $this->resumenSicom($this->kardex->acpm()['rows'], $desde, $hasta),
        ];
        $sicom['total'] = [];
        foreach (array_keys($sicom['corriente']) as $clave) {
            $sicom['total'][$clave] = $sicom['corriente'][$clave] + $sicom['acpm'][$clave];
        }

        return [
            'mes' => $mes,
            'desde' => $desde,
            'hasta' => $hasta,
            'lecturas' => $this->lecturas($desde, $hasta),
            'compras' => $this->compras($desde, $hasta),
            'sicom' => $sicom,
        ];
    }

    /**
     * Lectura inicial y final del mes de cada manguera. La inicial es la final del último día
     * registrado antes del mes (si no hay, la inicial del primer día del mes); la final, la del
     * último día del mes con lectura. Se avisa si ese día no es el último del mes.
     *
     * @return array{filas: list<array<string, mixed>>, totales: array{plus: float, acpm: float, total: float}, incompletas: list<string>}
     */
    private function lecturas(string $desde, string $hasta): array
    {
        $filas = [];
        $totales = ['plus' => 0.0, 'acpm' => 0.0, 'total' => 0.0];
        $incompletas = [];

        foreach (LecturaElectronica::MANGUERAS as $manguera => $combustible) {
            $anterior = LecturaElectronica::query()
                ->where('manguera', $manguera)
                ->whereDate('fecha', '<', $desde)
                ->whereNotNull('lectura_final')
                ->orderByDesc('fecha')
                ->first();
            $primera = $anterior ? null : LecturaElectronica::query()
                ->where('manguera', $manguera)
                ->whereDate('fecha', '>=', $desde)
                ->whereDate('fecha', '<=', $hasta)
                ->orderBy('fecha')
                ->first();
            $ultima = LecturaElectronica::query()
                ->where('manguera', $manguera)
                ->whereDate('fecha', '>=', $desde)
                ->whereDate('fecha', '<=', $hasta)
                ->whereNotNull('lectura_final')
                ->orderByDesc('fecha')
                ->first();

            $inicial = $anterior ? (float) $anterior->lectura_final : ($primera ? (float) $primera->lectura_inicial : null);
            $final = $ultima ? (float) $ultima->lectura_final : null;
            $venta = $inicial !== null && $final !== null ? max(0.0, $final - $inicial) : 0.0;
            $plus = $combustible === 'corriente' ? $venta : 0.0;
            $acpm = $combustible === 'acpm' ? $venta : 0.0;

            if ($ultima?->fecha->format('Y-m-d') !== $hasta) {
                $incompletas[] = $manguera;
            }

            $filas[] = [
                'manguera' => $manguera,
                'combustible' => $combustible,
                'inicial' => $inicial,
                'final' => $final,
                'fecha_final' => $ultima?->fecha->format('Y-m-d'),
                'plus' => $plus,
                'acpm' => $acpm,
                'total' => $venta,
            ];

            $totales['plus'] += $plus;
            $totales['acpm'] += $acpm;
            $totales['total'] += $venta;
        }

        return ['filas' => $filas, 'totales' => $totales, 'incompletas' => $incompletas];
    }

    /**
     * Facturas de combustible del mes con sus galones.
     *
     * @return array{filas: list<array{fecha: string, factura: string, plus: float, acpm: float, total: float}>, totales: array{plus: float, acpm: float, total: float}}
     */
    private function compras(string $desde, string $hasta): array
    {
        $filas = Compra::query()
            ->whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hasta)
            ->where(fn ($consulta) => $consulta->where('gasolina', '>', 0)->orWhere('acpm', '>', 0))
            ->orderBy('fecha')
            ->orderBy('factura')
            ->get()
            ->map(fn (Compra $compra): array => [
                'fecha' => $compra->fecha->format('Y-m-d'),
                'factura' => (string) $compra->factura,
                'plus' => (float) $compra->gasolina,
                'acpm' => (float) $compra->acpm,
                'total' => (float) $compra->gasolina + (float) $compra->acpm,
            ])
            ->all();

        return [
            'filas' => $filas,
            'totales' => [
                'plus' => (float) array_sum(array_column($filas, 'plus')),
                'acpm' => (float) array_sum(array_column($filas, 'acpm')),
                'total' => (float) array_sum(array_column($filas, 'total')),
            ],
        ];
    }

    /**
     * Movimiento del mes de un producto según su kardex. Un saldo inicial cargado dentro del
     * mes cuenta como saldo al inicio (el kardex arranca ese día).
     *
     * @param  list<array<string, mixed>>  $filas
     * @return array{saldo_inicio: float, compras: float, ventas: float, ajustes: float, saldo_cierre: float, saldo_inventario: float, diferencia: float}
     */
    private function resumenSicom(array $filas, string $desde, string $hasta): array
    {
        $saldoInicio = 0.0;
        $saldoInventario = 0.0;
        $compras = 0.0;
        $ventas = 0.0;
        $ajustes = 0.0;

        foreach ($filas as $fila) {
            $fecha = $fila['fecha_compra'] ?? $fila['fecha_venta'];

            if ($fecha > $hasta) {
                break;
            }

            $saldoInventario = (float) $fila['saldo_unidades'];

            if ($fecha < $desde) {
                $saldoInicio = $saldoInventario;

                continue;
            }

            match ($fila['tipo']) {
                'saldo_inicial' => $saldoInicio += (float) $fila['entradas'],
                'compra' => $compras += (float) $fila['entradas'],
                'venta' => $ventas += (float) $fila['salidas'],
                'ajuste' => $ajustes += (float) $fila['entradas'] - (float) $fila['salidas'],
                default => null,
            };
        }

        $saldoCierre = $saldoInicio + $compras - $ventas + $ajustes;

        return [
            'saldo_inicio' => $saldoInicio,
            'compras' => $compras,
            'ventas' => $ventas,
            'ajustes' => $ajustes,
            'saldo_cierre' => $saldoCierre,
            'saldo_inventario' => $saldoInventario,
            'diferencia' => round($saldoCierre - $saldoInventario, 3),
        ];
    }
}
