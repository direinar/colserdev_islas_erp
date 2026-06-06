<?php

namespace App\Services;

use App\DTOs\TurnoDTO;

class TurnoCalculatorService
{
    /**
     * Perform basic calculations and totals used by the planilla.
     * Returns an array of computed totals that can be merged into the TurnoDTO.
     */
    public function calculateFromDTO(TurnoDTO $dto): array
    {
        $rows = $dto->get('rows', []);

        $totales = [
            'total_galones_cte' => 0.0,
            'total_galones_acpm' => 0.0,
            'total_valor_cte' => 0.0,
            'total_valor_acpm' => 0.0,
            'total_venta_iapropiada' => 0.0,
        ];

        // If DTO contains explicit prices use them
        $precioCte = (float) $dto->get('precio_cte', 0);
        $precioAcpm = (float) $dto->get('precio_acpm', 0);

        foreach ($rows as $r) {
            // Expecting headers like 'surtidor', 'tipo', 'galones', 'valor'
            $tipo = strtoupper(trim((string) ($r['tipo'] ?? '')));
            $gal = (float) str_replace([',', ' '], ['', ''], ($r['galones'] ?? 0));
            $valor = (float) str_replace([',', ' '], ['', ''], ($r['valor'] ?? 0));

            if ($tipo === 'CTE') {
                $totales['total_galones_cte'] += $gal;
                $totales['total_valor_cte'] += $valor > 0 ? $valor : ($gal * $precioCte);
            } elseif ($tipo === 'ACPM') {
                $totales['total_galones_acpm'] += $gal;
                $totales['total_valor_acpm'] += $valor > 0 ? $valor : ($gal * $precioAcpm);
            }
        }

        $totales['total_venta_iapropiada'] = $totales['total_valor_cte'] + $totales['total_valor_acpm'];

        return $totales;
    }
}
