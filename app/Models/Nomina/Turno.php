<?php

namespace App\Models\Nomina;

use Database\Factories\Nomina\TurnoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Turno del catálogo que se asigna en la planilla. Si hora_fin es menor o igual
 * a hora_inicio, el turno termina al día siguiente (p. ej. 22:00 - 06:00).
 */
class Turno extends Model
{
    /** @use HasFactory<TurnoFactory> */
    use HasFactory;

    protected $table = 'nomina_turnos';

    protected $fillable = [
        'nombre',
        'hora_inicio',
        'hora_fin',
        'color',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function minutoInicio(): int
    {
        return self::minutosDelDia((string) $this->hora_inicio);
    }

    public function terminaAlDiaSiguiente(): bool
    {
        return self::minutosDelDia((string) $this->hora_fin) <= $this->minutoInicio();
    }

    public function duracionEnMinutos(): int
    {
        $inicio = $this->minutoInicio();
        $fin = self::minutosDelDia((string) $this->hora_fin);

        return $fin > $inicio ? $fin - $inicio : $fin + 1440 - $inicio;
    }

    public function horario(): string
    {
        return substr((string) $this->hora_inicio, 0, 5).' - '.substr((string) $this->hora_fin, 0, 5);
    }

    /**
     * Convierte una hora "HH:MM" o "HH:MM:SS" en minutos desde la medianoche.
     */
    public static function minutosDelDia(string $hora): int
    {
        [$horas, $minutos] = array_map('intval', array_slice(explode(':', $hora), 0, 2));

        return $horas * 60 + $minutos;
    }
}
