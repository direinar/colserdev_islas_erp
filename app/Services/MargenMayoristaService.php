<?php

namespace App\Services;

use App\Models\Compra;
use App\Models\MargenMayorista;
use Illuminate\Support\Collection;

/**
 * Informe de margen mayorista: galones comprados en el periodo (gasolina + ACPM, módulo de
 * compras) x porcentaje de mayorista x margen por galón del convenio. Cada factura toma los
 * parámetros vigentes en su fecha; el valor se liquida por grupo de parámetros sobre el total
 * de galones, como en la plantilla, para no acumular redondeos factura por factura.
 */
class MargenMayoristaService
{
    /**
     * @return array{
     *     facturas: list<array{fecha: string, factura: string, valor: float, gasolina: float, acpm: float, galones: float}>,
     *     totales: array{valor: float, gasolina: float, acpm: float, galones: float},
     *     liquidacion: list<array{vigente_desde: string|null, galones: float, porcentaje: float, margen: float, valor: float}>,
     *     margen_mayorista: float,
     *     parametros: Collection<int, MargenMayorista>
     * }
     */
    public function informe(string $desde, string $hasta): array
    {
        $parametros = MargenMayorista::query()->orderBy('vigente_desde')->get();

        $facturas = Compra::query()
            ->whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hasta)
            ->orderBy('fecha')
            ->orderBy('factura')
            ->get()
            ->map(fn (Compra $compra): array => [
                'fecha' => $compra->fecha->format('Y-m-d'),
                'factura' => (string) $compra->factura,
                'valor' => (float) $compra->vr_total_fra,
                'gasolina' => (float) $compra->gasolina,
                'acpm' => (float) $compra->acpm,
                'galones' => (float) $compra->gasolina + (float) $compra->acpm,
            ]);

        $liquidacion = $facturas
            ->groupBy(fn (array $factura): string => (string) $this->vigente($parametros, $factura['fecha'])?->id)
            ->map(function (Collection $grupo) use ($parametros): array {
                $parametro = $this->vigente($parametros, $grupo->first()['fecha']);
                $galones = (float) $grupo->sum('galones');
                $porcentaje = (float) ($parametro?->porcentaje ?? 0);
                $margen = (float) ($parametro?->margen ?? 0);

                return [
                    'vigente_desde' => $parametro?->vigente_desde->format('Y-m-d'),
                    'galones' => $galones,
                    'porcentaje' => $porcentaje,
                    'margen' => $margen,
                    'valor' => round($galones * $porcentaje * $margen, 0),
                ];
            })
            ->sortBy('vigente_desde')
            ->values()
            ->all();

        return [
            'facturas' => $facturas->all(),
            'totales' => [
                'valor' => (float) $facturas->sum('valor'),
                'gasolina' => (float) $facturas->sum('gasolina'),
                'acpm' => (float) $facturas->sum('acpm'),
                'galones' => (float) $facturas->sum('galones'),
            ],
            'liquidacion' => $liquidacion,
            'margen_mayorista' => (float) array_sum(array_column($liquidacion, 'valor')),
            'parametros' => $parametros,
        ];
    }

    /**
     * @param  Collection<int, MargenMayorista>  $parametros
     */
    private function vigente(Collection $parametros, string $fecha): ?MargenMayorista
    {
        return $parametros
            ->filter(fn (MargenMayorista $parametro): bool => $parametro->vigente_desde->format('Y-m-d') <= $fecha)
            ->last();
    }
}
