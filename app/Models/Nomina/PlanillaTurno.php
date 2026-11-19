<?php

namespace App\Models\Nomina;

use Carbon\CarbonImmutable;
use Database\Factories\Nomina\PlanillaTurnoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanillaTurno extends Model
{
    /** @use HasFactory<PlanillaTurnoFactory> */
    use HasFactory;

    public const ESTADO_BORRADOR = 'borrador';

    public const ESTADO_CERRADA = 'cerrada';

    protected $table = 'nomina_planillas_turnos';

    protected $fillable = [
        'anio',
        'mes',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'anio' => 'integer',
        'mes' => 'integer',
    ];

    public function asignaciones(): HasMany
    {
        return $this->hasMany(AsignacionTurno::class);
    }

    public function inicioDelMes(): CarbonImmutable
    {
        return CarbonImmutable::create($this->anio, $this->mes, 1);
    }

    public function finDelMes(): CarbonImmutable
    {
        return $this->inicioDelMes()->endOfMonth()->startOfDay();
    }

    public function nombreDelMes(): string
    {
        return mb_strtoupper($this->inicioDelMes()->locale('es')->translatedFormat('F Y'));
    }

    public function estaCerrada(): bool
    {
        return $this->estado === self::ESTADO_CERRADA;
    }
}
