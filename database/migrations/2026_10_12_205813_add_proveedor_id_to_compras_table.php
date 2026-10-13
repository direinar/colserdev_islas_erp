<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Proveedor de cada compra de combustible (como en compras de lubricantes).
     */
    public function up(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->foreignId('proveedor_id')
                ->nullable()
                ->after('factura')
                ->constrained('proveedores')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->dropConstrainedForeignId('proveedor_id');
        });
    }
};
