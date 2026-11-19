<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo de turnos que se asignan en la planilla. Si la hora final es menor
     * o igual a la inicial, el turno termina al día siguiente (p. ej. 10PM-6AM).
     */
    public function up(): void
    {
        Schema::create('nomina_turnos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 30)->unique();
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->string('color', 7)->default('#cfe2ff');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nomina_turnos');
    }
};
