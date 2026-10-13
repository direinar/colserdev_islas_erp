<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Existencia con la que arranca cada kardex (gasolina, ACPM y cada referencia de
     * canastilla): unidades y costo unitario a la fecha de corte.
     */
    public function up(): void
    {
        Schema::create('inventario_saldos_iniciales', function (Blueprint $table) {
            $table->id();
            $table->string('inventario', 20);
            // Referencia de canastilla; vacía para gasolina y ACPM.
            $table->string('referencia', 150)->default('');
            $table->date('fecha_corte');
            $table->decimal('unidades', 14, 3);
            $table->decimal('costo_unitario', 14, 2);
            $table->timestamps();

            $table->unique(['inventario', 'referencia']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_saldos_iniciales');
    }
};
