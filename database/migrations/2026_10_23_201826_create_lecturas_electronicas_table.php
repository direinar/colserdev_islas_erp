<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lectura electrónica de los surtidores por fecha (ya no por turno en la planilla): una
     * fila por manguera y día con su lectura inicial y final.
     */
    public function up(): void
    {
        Schema::create('lecturas_electronicas', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('manguera', 20);
            $table->string('combustible', 10);
            $table->decimal('lectura_inicial', 12, 3)->default(0);
            $table->decimal('lectura_final', 12, 3)->nullable();
            $table->decimal('galones', 12, 3)->default(0);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['fecha', 'manguera']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecturas_electronicas');
    }
};
