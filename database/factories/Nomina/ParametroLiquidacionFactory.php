<?php

namespace Database\Factories\Nomina;

use App\Models\Nomina\ParametroLiquidacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ParametroLiquidacion>
 */
class ParametroLiquidacionFactory extends Factory
{
    /**
     * Valores de la tabla de parámetros de la guía de nómina (2026).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vigente_desde' => '2026-01-01',
            'descripcion' => 'Vigencia 2026',
            'salario_minimo' => 1750905,
            'auxilio_transporte' => 249095,
            'horas_mensuales' => 220,
            'horas_semanales' => 42,
            'hora_inicio_nocturna' => '19:00',
            'hora_fin_nocturna' => '06:00',
            'hora_inicio_festiva' => '06:00',
            'hora_fin_festiva' => '00:00',
            'porcentaje_recargo_nocturno' => 35,
            'porcentaje_recargo_festivo' => 80,
            'porcentaje_extra_diurna' => 25,
            'porcentaje_extra_nocturna' => 75,
            'porcentaje_salud_empleado' => 4,
            'porcentaje_pension_empleado' => 4,
            'porcentaje_pension_empleador' => 16,
            'porcentaje_salud_empleador' => 4,
            'porcentaje_riesgos_laborales' => 2.436,
            'porcentaje_caja_compensacion' => 4,
            'porcentaje_provision_cesantias' => 8.33,
            'porcentaje_provision_intereses_cesantias' => 1,
            'porcentaje_provision_vacaciones' => 4.17,
            'porcentaje_provision_prima' => 8.33,
        ];
    }
}
