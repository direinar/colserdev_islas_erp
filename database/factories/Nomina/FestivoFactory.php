<?php

namespace Database\Factories\Nomina;

use App\Models\Nomina\Festivo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Festivo>
 */
class FestivoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fecha' => fake()->unique()->dateTimeBetween('2026-01-01', '2026-12-31')->format('Y-m-d'),
            'nombre' => 'Festivo',
        ];
    }
}
