<?php

namespace App\Services;

use App\Models\Compra;
use App\Models\LiquidacionAnticipoBimestral;
use App\Models\MargenIntermediacion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Informe de anticipo bimestral (régimen simple e ICA). Se liquida con los galones
 * comprados (gasolina + ACPM) de cada mes por el margen de intermediación vigente el
 * primer día del mes. Las ventas de lubricantes salen del control de canastilla (sin IVA);
 * solo "otros ingresos" y "descuentos en ventas" se digitan.
 */
class AnticipoBimestralService
{
    public const MESES = [
        1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO', 4 => 'ABRIL', 5 => 'MAYO', 6 => 'JUNIO',
        7 => 'JULIO', 8 => 'AGOSTO', 9 => 'SEPTIEMBRE', 10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE',
    ];

    public function __construct(private KardexInventarioService $kardex) {}

    /**
     * @return array<string, mixed>
     */
    public function informe(int $anio, int $bimestre): array
    {
        $desde = Carbon::create($anio, $bimestre * 2 - 1, 1)->startOfDay();
        $hasta = $desde->copy()->addMonth()->endOfMonth();
        $margenes = MargenIntermediacion::query()->orderBy('vigente_desde')->get();

        $comprasDelAnio = Compra::query()
            ->whereDate('fecha', '>=', $anio.'-01-01')
            ->whereDate('fecha', '<=', $hasta->format('Y-m-d'))
            ->orderBy('fecha')
            ->orderBy('factura')
            ->get();

        $galonesPorMes = $comprasDelAnio
            ->groupBy(fn (Compra $compra): int => (int) $compra->fecha->format('n'))
            ->map(fn (Collection $compras): float => (float) $compras->sum(fn (Compra $c): float => (float) $c->gasolina + (float) $c->acpm));

        $mesInicial = $bimestre * 2 - 1;
        $meses = collect([$mesInicial, $mesInicial + 1])
            ->map(fn (int $mes): array => $this->liquidarMes($anio, $mes, $galonesPorMes, $margenes))
            ->all();

        $totalBimestre = [
            'galones' => array_sum(array_column($meses, 'galones')),
            'pesos' => array_sum(array_column($meses, 'pesos')),
        ];

        $acumulado = $this->acumulado($anio, $mesInicial + 1, $galonesPorMes, $margenes);

        $ventasLubricantes = (float) collect($this->kardex->canastillaControl()['rows'])
            ->filter(fn (array $fila): bool => $fila['tipo'] === 'venta'
                && $fila['fecha_venta'] >= $desde->format('Y-m-d')
                && $fila['fecha_venta'] <= $hasta->format('Y-m-d'))
            ->sum('valor_venta');

        $ajustes = LiquidacionAnticipoBimestral::query()->firstOrNew(['anio' => $anio, 'bimestre' => $bimestre]);
        $otrosIngresos = (float) $ajustes->otros_ingresos;
        $descuentos = (float) $ajustes->descuentos_ventas;
        $subtotal = $totalBimestre['pesos'] + round($ventasLubricantes, 0);
        $baseSimple = $subtotal + $otrosIngresos;

        return [
            'anio' => $anio,
            'bimestre' => $bimestre,
            'desde' => $desde->format('Y-m-d'),
            'hasta' => $hasta->format('Y-m-d'),
            'compras' => $this->comprasDelBimestre($comprasDelAnio, $desde, $hasta),
            'meses' => $meses,
            'totalBimestre' => $totalBimestre,
            'acumulado' => $acumulado,
            'liquidacion' => [
                'ventas_combustible' => $totalBimestre['pesos'],
                'ventas_lubricantes' => round($ventasLubricantes, 0),
                'subtotal' => $subtotal,
                'otros_ingresos' => $otrosIngresos,
                'base_simple' => $baseSimple,
                'descuentos_ventas' => $descuentos,
                'base_ica' => $baseSimple - $descuentos,
            ],
            'margenes' => $margenes,
        ];
    }

    /**
     * Margen vigente el primer día del mes.
     *
     * @param  Collection<int, MargenIntermediacion>  $margenes
     */
    public function margenDelMes(int $anio, int $mes, Collection $margenes): float
    {
        $inicioMes = Carbon::create($anio, $mes, 1)->format('Y-m-d');

        return (float) ($margenes
            ->filter(fn (MargenIntermediacion $margen): bool => $margen->vigente_desde->format('Y-m-d') <= $inicioMes)
            ->last()?->valor ?? 0);
    }

    /**
     * @param  Collection<int, float>  $galonesPorMes
     * @param  Collection<int, MargenIntermediacion>  $margenes
     * @return array{mes: int, nombre: string, galones: float, margen: float, pesos: float}
     */
    private function liquidarMes(int $anio, int $mes, Collection $galonesPorMes, Collection $margenes): array
    {
        $galones = (float) ($galonesPorMes->get($mes) ?? 0);
        $margen = $this->margenDelMes($anio, $mes, $margenes);

        return [
            'mes' => $mes,
            'nombre' => self::MESES[$mes],
            'galones' => $galones,
            'margen' => $margen,
            'pesos' => round($galones * $margen, 0),
        ];
    }

    /**
     * Acumulado del año al cierre del bimestre: enero con su propio margen y de febrero
     * a la fecha de corte, cada mes con su margen.
     *
     * @param  Collection<int, float>  $galonesPorMes
     * @param  Collection<int, MargenIntermediacion>  $margenes
     * @return array{filas: list<array{nombre: string, galones: float, margen: float|null, pesos: float}>, total: array{galones: float, pesos: float}}
     */
    private function acumulado(int $anio, int $mesFinal, Collection $galonesPorMes, Collection $margenes): array
    {
        $enero = $this->liquidarMes($anio, 1, $galonesPorMes, $margenes);
        $resto = collect(range(2, $mesFinal))->map(fn (int $mes): array => $this->liquidarMes($anio, $mes, $galonesPorMes, $margenes));
        $margenesResto = $resto->pluck('margen')->unique();

        $filas = [
            ['nombre' => 'ENERO', 'galones' => $enero['galones'], 'margen' => $enero['margen'], 'pesos' => $enero['pesos']],
            [
                'nombre' => $mesFinal === 2 ? 'FEBRERO' : 'FEBRERO A '.self::MESES[$mesFinal],
                'galones' => (float) $resto->sum('galones'),
                // Si el margen cambió dentro del periodo no hay un único valor que mostrar.
                'margen' => $margenesResto->count() === 1 ? (float) $margenesResto->first() : null,
                'pesos' => (float) $resto->sum('pesos'),
            ],
        ];

        return [
            'filas' => $filas,
            'total' => [
                'galones' => $filas[0]['galones'] + $filas[1]['galones'],
                'pesos' => $filas[0]['pesos'] + $filas[1]['pesos'],
            ],
        ];
    }

    /**
     * Compras de combustible del bimestre agrupadas por mes, con su subtotal de galones.
     *
     * @param  Collection<int, Compra>  $compras
     * @return list<array{nombre: string, compras: Collection<int, Compra>, gasolina: float, acpm: float, galones: float, valor: float}>
     */
    private function comprasDelBimestre(Collection $compras, Carbon $desde, Carbon $hasta): array
    {
        return $compras
            ->filter(fn (Compra $compra): bool => $compra->fecha->betweenIncluded($desde, $hasta))
            ->groupBy(fn (Compra $compra): int => (int) $compra->fecha->format('n'))
            ->map(fn (Collection $delMes, int $mes): array => [
                'nombre' => self::MESES[$mes],
                'compras' => $delMes->values(),
                'gasolina' => (float) $delMes->sum('gasolina'),
                'acpm' => (float) $delMes->sum('acpm'),
                'galones' => (float) $delMes->sum(fn (Compra $c): float => (float) $c->gasolina + (float) $c->acpm),
                'valor' => (float) $delMes->sum('vr_total_fra'),
            ])
            ->values()
            ->all();
    }
}
