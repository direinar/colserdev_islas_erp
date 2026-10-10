<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Saldos iniciales del contrato de galonaje con el proveedor, a una fecha de corte.
     * Desde esa fecha, los galones vendidos (gasolina + ACPM) se suman al galonaje
     * acumulado y se restan del saldo del contrato (Informe de Cierre, renglones 14 y 15).
     */
    public function up(): void
    {
        Schema::create('galonaje_contrato', function (Blueprint $table) {
            $table->id();
            $table->date('fecha_corte');
            $table->decimal('galonaje_acumulado_inicial', 16, 3);
            $table->decimal('saldo_contrato_inicial', 16, 3);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('galonaje_contrato');
    }
};
