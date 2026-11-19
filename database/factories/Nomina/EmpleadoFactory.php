<?php

namespace Database\Factories\Nomina;

use App\Models\Nomina\Empleado;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Empleado>
 */
class EmpleadoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nombres = fake()->firstName();

        return [
            'tipo_documento' => 'CC',
            'numero_documento' => (string) fake()->unique()->numberBetween(10000000, 1999999999),
            'nombres' => $nombres,
            'apellidos' => fake()->lastName().' '.fake()->lastName(),
            'nombre_corto' => mb_strtoupper($nombres),
            'cargo' => 'OPERARIO DE ISLAS',
            'fecha_ingreso' => fake()->dateTimeBetween('-5 years', '-1 month')->format('Y-m-d'),
            'tipo_contrato' => 'indefinido',
            'fecha_vencimiento_contrato' => null,
            'salario_basico' => 1750905,
            'recibe_auxilio_transporte' => true,
            'aplica_recargos' => true,
            'forma_pago' => 'transferencia',
            'banco' => 'Davivienda',
            'tipo_cuenta' => 'ahorros',
            'numero_cuenta' => (string) fake()->numerify('##########'),
            'activo' => true,
        ];
    }

    public function retirado(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => false,
            'fecha_retiro' => now()->subDays(10)->toDateString(),
            'motivo_retiro' => 'Renuncia voluntaria',
        ]);
    }
}
