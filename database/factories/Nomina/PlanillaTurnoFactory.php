<?php

namespace Database\Factories\Nomina;

use App\Models\Nomina\PlanillaTurno;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlanillaTurno>
 */
class PlanillaTurnoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'anio' => 2026,
            'mes' => 11,
            'estado' => PlanillaTurno::ESTADO_BORRADOR,
        ];
    }

    public function cerrada(): static
    {
        return $this->state(fn (array $attributes) => ['estado' => PlanillaTurno::ESTADO_CERRADA]);
    }
}
