<?php

namespace Database\Factories\Nomina;

use App\Models\Nomina\LiquidacionNomina;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LiquidacionNomina>
 */
class LiquidacionNominaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'anio' => 2026,
            'mes' => 11,
            'estado' => LiquidacionNomina::ESTADO_BORRADOR,
        ];
    }
}
