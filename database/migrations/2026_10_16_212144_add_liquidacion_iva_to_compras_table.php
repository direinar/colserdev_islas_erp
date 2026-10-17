<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Liquidación del costo de cada combustible de la factura (plantilla "MODULO COMPRAS
     * ADICIONADO IVA"): bases del 19% y del 5%, IVA liquidado, costo del producto, sobretasa
     * y Soldicom. El costo total sigue en distribucion_gasolina / distribucion_acpm.
     */
    public function up(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            foreach (['gasolina', 'acpm'] as $combustible) {
                $table->decimal('base19_'.$combustible, 14, 2)->default(0);
                $table->decimal('base5_'.$combustible, 14, 2)->default(0);
                $table->decimal('iva_'.$combustible, 14, 2)->default(0);
                $table->decimal('costo_producto_'.$combustible, 14, 2)->default(0);
                $table->decimal('sobretasa_'.$combustible, 14, 2)->default(0);
                $table->decimal('soldicom_'.$combustible, 14, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            foreach (['gasolina', 'acpm'] as $combustible) {
                $table->dropColumn([
                    'base19_'.$combustible, 'base5_'.$combustible, 'iva_'.$combustible,
                    'costo_producto_'.$combustible, 'sobretasa_'.$combustible, 'soldicom_'.$combustible,
                ]);
            }
        });
    }
};
