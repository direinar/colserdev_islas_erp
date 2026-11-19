<?php

namespace App\Models\Nomina;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Liquidación del contrato de trabajo y prestaciones sociales al retiro (hoja LIQUIDADOR PREST. SOC.).
 */
class LiquidacionContrato extends Model
{
    protected $table = 'nomina_liquidaciones_contrato';

    protected $fillable = [
        'empleado_id', 'fecha_ingreso', 'fecha_retiro', 'motivo_retiro', 'salario_base',
        'dias_anio', 'dias_semestre', 'dias_contrato',
        'sueldo_pendiente', 'recargos_nocturnos', 'recargos_festivos', 'auxilio_transporte',
        'cesantias', 'intereses_cesantias', 'prima', 'vacaciones',
        'salud', 'pension', 'otras_deducciones', 'neto_pagar', 'observaciones', 'elaborada_por',
    ];

    protected $casts = [
        'fecha_ingreso' => 'date',
        'fecha_retiro' => 'date',
        'salario_base' => 'float',
        'dias_anio' => 'integer',
        'dias_semestre' => 'integer',
        'dias_contrato' => 'integer',
        'sueldo_pendiente' => 'float',
        'recargos_nocturnos' => 'float',
        'recargos_festivos' => 'float',
        'auxilio_transporte' => 'float',
        'cesantias' => 'float',
        'intereses_cesantias' => 'float',
        'prima' => 'float',
        'vacaciones' => 'float',
        'salud' => 'float',
        'pension' => 'float',
        'otras_deducciones' => 'float',
        'neto_pagar' => 'float',
    ];

    /**
     * @return BelongsTo<Empleado, $this>
     */
    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function subtotalSalarios(): float
    {
        return $this->sueldo_pendiente + $this->recargos_nocturnos + $this->recargos_festivos + $this->auxilio_transporte;
    }

    public function subtotalPrestaciones(): float
    {
        return $this->cesantias + $this->intereses_cesantias + $this->prima + $this->vacaciones;
    }

    public function totalDeducciones(): float
    {
        return $this->salud + $this->pension + $this->otras_deducciones;
    }
}
