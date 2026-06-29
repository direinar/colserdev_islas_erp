<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('turno_surtidores', function (Blueprint $table) {
    $table->id();
    $table->foreignId('turno_id')->constrained('turnos')->cascadeOnDelete();
    $table->string('manguera');        // "PLUS 01", "ACPM 03"
    $table->string('combustible');     // 'corriente' | 'acpm'
    $table->decimal('lectura_inicial', 12, 3)->default(0);
    $table->decimal('lectura_final', 12, 3)->default(0);
    $table->decimal('galones', 10, 3)->default(0); // calculado
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('turno_surtidores');
    }
};
