<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Historial de guardados de cada planilla de turno: quién la guardó y cuántas filas y qué
     * valor tenía cada sección. Si un guardado quita filas o baja el total de ventas queda una
     * alerta (control de planillas borradas o incompletas).
     */
    public function up(): void
    {
        Schema::create('turno_auditorias', function (Blueprint $table) {
            $table->id();
            // Se conserva aunque la planilla se elimine de la base de datos.
            $table->foreignId('turno_id')->nullable()->constrained('turnos')->nullOnDelete();
            $table->unsignedInteger('numero_turno');
            $table->string('accion', 20);
            $table->json('resumen');
            $table->text('alerta')->nullable();
            $table->timestamp('atendida_at')->nullable();
            $table->string('atendida_por')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('atendida_at');
            $table->index('numero_turno');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turno_auditorias');
    }
};
