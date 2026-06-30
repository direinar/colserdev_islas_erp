<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cartera_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->date('fecha_inicial')->nullable();
            $table->date('fecha_final')->nullable();
            $table->string('cuenta', 50)->default('');
            $table->string('concepto', 150)->default('');
            $table->string('tercero', 180)->default('');
            $table->string('nit', 60)->default('');
            $table->decimal('debito', 15, 0)->default(0);
            $table->decimal('credito', 15, 0)->default(0);
            $table->timestamps();

            $table->index(['fecha_inicial']);
            $table->index(['fecha_final']);
            $table->index(['cuenta']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cartera_movimientos');
    }
};
