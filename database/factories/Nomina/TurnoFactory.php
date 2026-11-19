<?php

namespace Database\Factories\Nomina;

use App\Models\Nomina\Turno;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Turno>
 */
class TurnoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->bothify('T-###'),
            'hora_inicio' => '06:00',
            'hora_fin' => '14:00',
            'color' => '#cfe2ff',
            'activo' => true,
        ];
    }
}
