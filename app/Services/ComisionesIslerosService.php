<?php

namespace App\Services;

use App\Models\ComisionPorcentaje;
use App\Models\Turno;
use Illuminate\Support\Collection;

/**
 * Ventas de combustible y canastilla por islero en un periodo, con su comisión para el pago.
 *
 * Cada planilla de turno (no anulada) suma al vendedor que la registró (nombre_vendedor, que se
 * conserva aunque otro usuario la corrija). Combustible = valor según cierres de IAPROPIADA
 * (corriente + ACPM); canastilla = total vendido con IVA. Comisión = venta × % del tipo.
 */
class ComisionesIslerosService
{
    /**
     * @return array{
     *     filas: list<array{islero: string, planillas: int, galones: float, combustible: float, comision_combustible: float, canastilla: float, comision_canastilla: float, comision_total: float}>,
     *     totales: array{planillas: int, galones: float, combustible: float, comision_combustible: float, canastilla: float, comision_canastilla: float, comision_total: float},
     *     porcentajes: ComisionPorcentaje
     * }
     */
    public function informe(string $desde, string $hasta): array
    {
        $porcentajes = ComisionPorcentaje::vigentes();
        $porcentajeCombustible = (float) $porcentajes->combustible / 100;
        $porcentajeCanastilla = (float) $porcentajes->canastilla / 100;

        $filas = Turno::query()
            ->noAnulados()
            ->whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hasta)
            ->withSum('lubricantes', 'total')
            ->get()
            ->groupBy(fn (Turno $turno): string => mb_strtoupper(trim((string) $turno->nombre_vendedor)) ?: 'SIN VENDEDOR')
            ->map(function (Collection $turnos, string $islero) use ($porcentajeCombustible, $porcentajeCanastilla): array {
                $combustible = round((float) $turnos->sum(fn (Turno $t): float => (float) $t->tirillas_valor_corriente + (float) $t->tirillas_valor_acpm));
                $canastilla = round((float) $turnos->sum('lubricantes_sum_total'));
                $comisionCombustible = round($combustible * $porcentajeCombustible);
                $comisionCanastilla = round($canastilla * $porcentajeCanastilla);

                return [
                    'islero' => $islero,
                    'planillas' => $turnos->count(),
                    'galones' => round((float) $turnos->sum(fn (Turno $t): float => (float) $t->tirillas_galones_corriente + (float) $t->tirillas_galones_acpm), 3),
                    'combustible' => $combustible,
                    'comision_combustible' => $comisionCombustible,
                    'canastilla' => $canastilla,
                    'comision_canastilla' => $comisionCanastilla,
                    'comision_total' => $comisionCombustible + $comisionCanastilla,
                ];
            })
            ->sortKeys()
            ->values();

        $totales = ['planillas' => (int) $filas->sum('planillas')];
        foreach (['galones', 'combustible', 'comision_combustible', 'canastilla', 'comision_canastilla', 'comision_total'] as $campo) {
            $totales[$campo] = (float) $filas->sum($campo);
        }

        return ['filas' => $filas->all(), 'totales' => $totales, 'porcentajes' => $porcentajes];
    }
}
