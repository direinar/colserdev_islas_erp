<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Planilla de turnos de un mes: la programación de cada empleado por día y,
     * a fin de mes, la base para liquidar los recargos nocturnos y festivos.
     */
    public function up(): void
    {
        Schema::create('nomina_planillas_turnos', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('anio');
            $table->unsignedTinyInteger('mes');
            $table->string('estado', 20)->default('borrador');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(['anio', 'mes']);
        });

        Schema::create('nomina_asignaciones_turno', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planilla_turno_id')->constrained('nomina_planillas_turnos')->cascadeOnDelete();
            $table->foreignId('empleado_id')->constrained('nomina_empleados')->restrictOnDelete();
            $table->foreignId('turno_id')->constrained('nomina_turnos')->restrictOnDelete();
            $table->date('fecha');
            $table->decimal('horas_trabajadas', 5, 2)->default(0);
            $table->decimal('horas_nocturnas', 5, 2)->default(0);
            $table->decimal('horas_festivas', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['planilla_turno_id', 'empleado_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nomina_asignaciones_turno');
        Schema::dropIfExists('nomina_planillas_turnos');
    }
};
