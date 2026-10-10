<?php

namespace App\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Rango desde/hasta de los informes: por defecto el mes en curso hasta hoy, y si las fechas
 * llegan invertidas se intercambian.
 */
trait ResuelveRangoFechas
{
    /**
     * @return array{0: string, 1: string}
     */
    protected function rango(Request $request, string $campoDesde = 'desde', string $campoHasta = 'hasta'): array
    {
        $request->validate([
            $campoDesde => ['nullable', 'date'],
            $campoHasta => ['nullable', 'date'],
        ]);

        $desde = Carbon::parse($request->query($campoDesde) ?: now()->startOfMonth());
        $hasta = Carbon::parse($request->query($campoHasta) ?: now());

        if ($hasta->lt($desde)) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        return [$desde->format('Y-m-d'), $hasta->format('Y-m-d')];
    }
}
