<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Valores que se digitan en la liquidación del anticipo bimestral: otros ingresos
     * y descuentos en ventas. Lo demás del informe se calcula desde compras y ventas.
     */
    public function up(): void
    {
        Schema::create('liquidaciones_anticipo_bimestral', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('anio');
            $table->unsignedTinyInteger('bimestre');
            $table->decimal('otros_ingresos', 16, 0)->default(0);
            $table->decimal('descuentos_ventas', 16, 0)->default(0);
            $table->timestamps();

            $table->unique(['anio', 'bimestre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liquidaciones_anticipo_bimestral');
    }
};
