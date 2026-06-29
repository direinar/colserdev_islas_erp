<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turnos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->unsignedSmallInteger('numero_turno');
            $table->string('nombre_vendedor')->nullable();
            $table->string('revisado_por')->nullable();
            $table->decimal('precio_corriente', 12, 2)->default(0);
            $table->decimal('precio_acpm', 12, 2)->default(0);
            $table->decimal('traslado_sobrante', 14, 2)->default(0);
            $table->decimal('traslado_faltante', 14, 2)->default(0);
            $table->timestamps();

            $table->unique(['fecha', 'numero_turno']); // conservas tu unique
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turnos');
    }
};
