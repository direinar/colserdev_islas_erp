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
        Schema::create('turno_recaudos', function (Blueprint $table) {
    $table->id();
    $table->foreignId('turno_id')->constrained('turnos')->cascadeOnDelete();
    $table->foreignId('cliente_id')->nullable()->constrained('customers')->nullOnDelete();
    $table->decimal('valor', 15, 2)->default(0);
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('turno_recaudos');
    }
};
