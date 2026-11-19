<?php

namespace App\Models\Nomina;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Fila de un empleado en la nómina del mes: novedades digitadas y valores calculados
 * (devengados, deducciones, aportes del empleador y provisiones).
 */
class LiquidacionNominaEmpleado extends Model
{
    /**
     * Valores que calcula NominaLiquidacionService::calcular().
     */
    public const CALCULADOS = [
        'basico', 'vacaciones', 'recargo_nocturno', 'recargo_festivo', 'base_aportes', 'auxilio_transporte',
        'total_devengado', 'salud', 'pension', 'total_deducciones', 'neto_pagar',
        'aporte_pension', 'aporte_salud', 'aporte_riesgos', 'aporte_caja',
        'provision_cesantias', 'provision_intereses', 'provision_vacaciones', 'provision_prima', 'costo_total',
    ];

    /**
     * Novedades que se digitan en la nómina del mes.
     */
    public const NOVEDADES = [
        'dias_trabajados', 'dias_vacaciones_disfrutadas', 'dias_vacaciones_dinero', 'horas_nocturnas', 'horas_festivas',
        'cesantias', 'intereses_cesantias', 'prima', 'anticipos', 'faltantes', 'medio_pago', 'observaciones',
    ];

    protected $table = 'nomina_liquidacion_empleados';

    protected $fillable = [
        'liquidacion_id', 'empleado_id', 'salario_basico', 'recibe_auxilio_transporte', 'aplica_recargos',
        ...self::NOVEDADES,
        ...self::CALCULADOS,
    ];

    protected $casts = [
        'salario_basico' => 'float',
        'recibe_auxilio_transporte' => 'boolean',
        'aplica_recargos' => 'boolean',
        'dias_trabajados' => 'integer',
        'dias_vacaciones_disfrutadas' => 'integer',
        'dias_vacaciones_dinero' => 'integer',
        'horas_nocturnas' => 'float',
        'horas_festivas' => 'float',
        'cesantias' => 'float',
        'intereses_cesantias' => 'float',
        'prima' => 'float',
        'anticipos' => 'float',
        'faltantes' => 'float',
        'basico' => 'float',
        'vacaciones' => 'float',
        'recargo_nocturno' => 'float',
        'recargo_festivo' => 'float',
        'base_aportes' => 'float',
        'auxilio_transporte' => 'float',
        'total_devengado' => 'float',
        'salud' => 'float',
        'pension' => 'float',
        'total_deducciones' => 'float',
        'neto_pagar' => 'float',
        'aporte_pension' => 'float',
        'aporte_salud' => 'float',
        'aporte_riesgos' => 'float',
        'aporte_caja' => 'float',
        'provision_cesantias' => 'float',
        'provision_intereses' => 'float',
        'provision_vacaciones' => 'float',
        'provision_prima' => 'float',
        'costo_total' => 'float',
    ];

    /**
     * @return BelongsTo<LiquidacionNomina, $this>
     */
    public function liquidacion(): BelongsTo
    {
        return $this->belongsTo(LiquidacionNomina::class, 'liquidacion_id');
    }

    /**
     * @return BelongsTo<Empleado, $this>
     */
    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function totalRecargos(): float
    {
        return $this->recargo_nocturno + $this->recargo_festivo;
    }

    public function totalAportes(): float
    {
        return $this->aporte_pension + $this->aporte_salud + $this->aporte_riesgos + $this->aporte_caja;
    }

    public function totalProvisiones(): float
    {
        return $this->provision_cesantias + $this->provision_intereses + $this->provision_vacaciones + $this->provision_prima;
    }
}
