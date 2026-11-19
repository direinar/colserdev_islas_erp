<?php

namespace App\Models\Nomina;

use Carbon\CarbonInterface;
use Database\Factories\Nomina\ParametroLiquidacionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Tabla de parámetros de liquidación de una vigencia. Rige desde vigente_desde
 * hasta el día anterior a la vigencia siguiente.
 */
class ParametroLiquidacion extends Model
{
    /** @use HasFactory<ParametroLiquidacionFactory> */
    use HasFactory;

    protected $table = 'nomina_parametros_liquidacion';

    protected $fillable = [
        'vigente_desde',
        'descripcion',
        'salario_minimo',
        'auxilio_transporte',
        'horas_mensuales',
        'horas_semanales',
        'hora_inicio_nocturna',
        'hora_fin_nocturna',
        'hora_inicio_festiva',
        'hora_fin_festiva',
        'porcentaje_recargo_nocturno',
        'porcentaje_recargo_festivo',
        'porcentaje_extra_diurna',
        'porcentaje_extra_nocturna',
        'porcentaje_salud_empleado',
        'porcentaje_pension_empleado',
        'porcentaje_pension_empleador',
        'porcentaje_salud_empleador',
        'porcentaje_riesgos_laborales',
        'porcentaje_caja_compensacion',
        'porcentaje_provision_cesantias',
        'porcentaje_provision_intereses_cesantias',
        'porcentaje_provision_vacaciones',
        'porcentaje_provision_prima',
    ];

    protected $casts = [
        'vigente_desde' => 'date',
        'salario_minimo' => 'decimal:2',
        'auxilio_transporte' => 'decimal:2',
        'horas_mensuales' => 'integer',
        'horas_semanales' => 'integer',
        'porcentaje_recargo_nocturno' => 'decimal:3',
        'porcentaje_recargo_festivo' => 'decimal:3',
        'porcentaje_extra_diurna' => 'decimal:3',
        'porcentaje_extra_nocturna' => 'decimal:3',
        'porcentaje_salud_empleado' => 'decimal:3',
        'porcentaje_pension_empleado' => 'decimal:3',
        'porcentaje_pension_empleador' => 'decimal:3',
        'porcentaje_salud_empleador' => 'decimal:3',
        'porcentaje_riesgos_laborales' => 'decimal:3',
        'porcentaje_caja_compensacion' => 'decimal:3',
        'porcentaje_provision_cesantias' => 'decimal:3',
        'porcentaje_provision_intereses_cesantias' => 'decimal:3',
        'porcentaje_provision_vacaciones' => 'decimal:3',
        'porcentaje_provision_prima' => 'decimal:3',
    ];

    /**
     * Porcentajes agrupados tal como se muestran en el formulario y en la ficha.
     *
     * @return array<string, array<string, string>>
     */
    public static function gruposPorcentajes(): array
    {
        return [
            'Recargos y horas extras' => [
                'porcentaje_recargo_nocturno' => 'Recargo nocturno',
                'porcentaje_recargo_festivo' => 'Recargo dominical y festivo',
                'porcentaje_extra_diurna' => 'Hora extra diurna',
                'porcentaje_extra_nocturna' => 'Hora extra nocturna',
            ],
            'Aportes del empleado (deducciones)' => [
                'porcentaje_salud_empleado' => 'Salud',
                'porcentaje_pension_empleado' => 'Pensión',
            ],
            'Aportes parafiscales (empleador)' => [
                'porcentaje_pension_empleador' => 'Pensión',
                'porcentaje_salud_empleador' => 'Salud',
                'porcentaje_riesgos_laborales' => 'Riesgos laborales (ARL)',
                'porcentaje_caja_compensacion' => 'Caja de compensación (CCF)',
            ],
            'Provisiones de prestaciones sociales' => [
                'porcentaje_provision_cesantias' => 'Cesantías',
                'porcentaje_provision_intereses_cesantias' => 'Intereses sobre cesantías',
                'porcentaje_provision_vacaciones' => 'Vacaciones',
                'porcentaje_provision_prima' => 'Prima legal',
            ],
        ];
    }

    /**
     * La vigencia que aplica en una fecha: la más reciente que empezó ese día o antes.
     */
    public static function vigenteEn(CarbonInterface $fecha): ?self
    {
        return self::query()
            ->whereDate('vigente_desde', '<=', $fecha->toDateString())
            ->orderByDesc('vigente_desde')
            ->first();
    }

    /**
     * Valor de la hora ordinaria: salario mínimo dividido entre las horas mensuales, redondeado
     * a pesos como en la tabla de parámetros de la plantilla ($7.959 en 2026).
     */
    public function valorHoraOrdinaria(): float
    {
        return round((float) $this->salario_minimo / $this->horas_mensuales);
    }

    public function valorHoraRecargoNocturno(): float
    {
        return $this->valorHoraOrdinaria() * (float) $this->porcentaje_recargo_nocturno / 100;
    }

    public function valorHoraRecargoFestivo(): float
    {
        return $this->valorHoraOrdinaria() * (float) $this->porcentaje_recargo_festivo / 100;
    }
}
