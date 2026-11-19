<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Parámetros de liquidación por vigencia: cada registro rige desde su fecha
     * hasta el día anterior a la siguiente vigencia (salario mínimo, auxilio de
     * transporte, jornada, recargos, aportes y provisiones).
     */
    public function up(): void
    {
        Schema::create('nomina_parametros_liquidacion', function (Blueprint $table) {
            $table->id();
            $table->date('vigente_desde')->unique();
            $table->string('descripcion')->nullable();

            $table->decimal('salario_minimo', 14, 2);
            $table->decimal('auxilio_transporte', 14, 2);
            $table->unsignedSmallInteger('horas_mensuales');
            $table->unsignedTinyInteger('horas_semanales');

            $table->time('hora_inicio_nocturna');
            $table->time('hora_fin_nocturna');
            $table->time('hora_inicio_festiva');
            $table->time('hora_fin_festiva');

            $table->decimal('porcentaje_recargo_nocturno', 6, 3);
            $table->decimal('porcentaje_recargo_festivo', 6, 3);
            $table->decimal('porcentaje_extra_diurna', 6, 3);
            $table->decimal('porcentaje_extra_nocturna', 6, 3);

            $table->decimal('porcentaje_salud_empleado', 6, 3);
            $table->decimal('porcentaje_pension_empleado', 6, 3);

            $table->decimal('porcentaje_pension_empleador', 6, 3);
            $table->decimal('porcentaje_salud_empleador', 6, 3);
            $table->decimal('porcentaje_riesgos_laborales', 6, 3);
            $table->decimal('porcentaje_caja_compensacion', 6, 3);

            $table->decimal('porcentaje_provision_cesantias', 6, 3);
            $table->decimal('porcentaje_provision_intereses_cesantias', 6, 3);
            $table->decimal('porcentaje_provision_vacaciones', 6, 3);
            $table->decimal('porcentaje_provision_prima', 6, 3);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nomina_parametros_liquidacion');
    }
};
