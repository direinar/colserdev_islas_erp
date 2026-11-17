<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Porcentajes de comisión de los isleros: uno para combustible y otro para canastilla,
     * iguales para todos, sobre el valor vendido. Una sola fila.
     */
    public function up(): void
    {
        Schema::create('comision_porcentajes', function (Blueprint $table) {
            $table->id();
            $table->decimal('combustible', 6, 3)->default(0);
            $table->decimal('canastilla', 6, 3)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comision_porcentajes');
    }
};
