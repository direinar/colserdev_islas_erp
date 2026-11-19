<?php

namespace App\Models\Nomina;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro del formato de control de vacaciones: días disfrutados o pagados en dinero.
 */
class Vacacion extends Model
{
    protected $table = 'nomina_vacaciones';

    protected $fillable = [
        'empleado_id',
        'fecha_inicio',
        'fecha_fin',
        'dias_disfrutados',
        'dias_dinero',
        'periodo',
        'observaciones',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'dias_disfrutados' => 'integer',
        'dias_dinero' => 'integer',
    ];

    /**
     * @return BelongsTo<Empleado, $this>
     */
    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }
}
