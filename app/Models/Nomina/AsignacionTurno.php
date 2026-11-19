<?php

namespace App\Models\Nomina;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Turno asignado a un empleado en un día de la planilla, con las horas
 * calculadas al guardar (trabajadas, de recargo nocturno y dominical/festivo).
 */
class AsignacionTurno extends Model
{
    protected $table = 'nomina_asignaciones_turno';

    protected $fillable = [
        'planilla_turno_id',
        'empleado_id',
        'turno_id',
        'fecha',
        'horas_trabajadas',
        'horas_nocturnas',
        'horas_festivas',
    ];

    protected $casts = [
        'fecha' => 'date',
        'horas_trabajadas' => 'float',
        'horas_nocturnas' => 'float',
        'horas_festivas' => 'float',
    ];

    public function planilla(): BelongsTo
    {
        return $this->belongsTo(PlanillaTurno::class, 'planilla_turno_id');
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function turno(): BelongsTo
    {
        return $this->belongsTo(Turno::class);
    }
}
