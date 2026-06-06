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
        Schema::create('planilla_surtidores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planilla_id')->constrained()->cascadeOnDelete();
            $table->string('nombre', 120);
            $table->string('producto', 80);
            $table->decimal('lectura_inicial', 14, 3)->default(0);
            $table->decimal('lectura_final', 14, 3)->default(0);
            $table->decimal('precio', 14, 2)->default(0);
            $table->decimal('galones', 14, 3)->default(0);
            $table->decimal('venta', 14, 2)->default(0);
            $table->unsignedInteger('orden')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planilla_surtidores');
    }
};
