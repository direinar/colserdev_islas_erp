<?php

namespace Database\Factories;

use App\Models\EventoAplicacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventoAplicacion>
 */
class EventoAplicacionFactory extends Factory
{
    /**
     * Por defecto, una falla crítica pendiente.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tipo' => EventoAplicacion::TIPO_ERROR,
            'nivel' => EventoAplicacion::NIVEL_CRITICO,
            'modulo' => 'Operaciones',
            'titulo' => fake()->sentence(4),
            'descripcion' => fake()->sentence(),
            'detalle' => null,
            'metodo' => 'POST',
            'url' => '/turnos',
            'ruta' => 'turnos.store',
            'estado_http' => 500,
            'huella' => fake()->sha1(),
            'ocurrencias' => 1,
            'ultima_vez' => now(),
            'estado' => EventoAplicacion::ESTADO_PENDIENTE,
        ];
    }

    public function advertencia(): static
    {
        return $this->state(fn (): array => [
            'tipo' => EventoAplicacion::TIPO_ACCION,
            'nivel' => EventoAplicacion::NIVEL_ADVERTENCIA,
            'estado_http' => 302,
        ]);
    }

    public function informativo(): static
    {
        return $this->state(fn (): array => [
            'tipo' => EventoAplicacion::TIPO_ACCION,
            'nivel' => EventoAplicacion::NIVEL_INFORMATIVO,
            'estado_http' => 302,
            'huella' => null,
            'estado' => EventoAplicacion::ESTADO_REGISTRADO,
        ]);
    }
}
