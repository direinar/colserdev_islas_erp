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
        Schema::create('turno_lubricantes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('turno_id')->constrained('turnos')->cascadeOnDelete();
    $table->unsignedInteger('cantidad')->default(0);
    $table->string('producto');        // referencia del producto
    $table->decimal('valor_sin_iva', 15, 2)->default(0);
    $table->decimal('iva', 15, 2)->default(0);
    $table->decimal('total', 15, 2)->default(0);
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('turno_lubricantes');
    }
};
