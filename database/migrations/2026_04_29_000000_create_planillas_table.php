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
        Schema::create('planillas', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->index();
            $table->string('turno', 50)->index();
            $table->string('islero', 120);
            $table->json('surtidores');
            $table->json('medios_pago');
            $table->json('cartera');
            $table->decimal('total_galones', 14, 3)->default(0);
            $table->decimal('total_ventas', 14, 2)->default(0);
            $table->decimal('total_recaudos', 14, 2)->default(0);
            $table->decimal('total_cartera', 14, 2)->default(0);
            $table->decimal('cuadre', 14, 2)->default(0);
            $table->string('estado', 30)->default('borrador');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planillas');
    }
};
