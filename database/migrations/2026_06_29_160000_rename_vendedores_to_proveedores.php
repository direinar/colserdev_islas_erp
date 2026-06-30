<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vendedores') && ! Schema::hasTable('proveedores')) {
            Schema::rename('vendedores', 'proveedores');
        }

        if (Schema::hasTable('compras_lubricantes') && Schema::hasColumn('compras_lubricantes', 'vendedor_id')) {
            Schema::table('compras_lubricantes', function (Blueprint $table) {
                $table->dropForeign(['vendedor_id']);
            });

            Schema::table('compras_lubricantes', function (Blueprint $table) {
                $table->renameColumn('vendedor_id', 'proveedor_id');
            });

            Schema::table('compras_lubricantes', function (Blueprint $table) {
                $table->foreign('proveedor_id')
                    ->references('id')
                    ->on('proveedores')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('compras_lubricantes') && Schema::hasColumn('compras_lubricantes', 'proveedor_id')) {
            Schema::table('compras_lubricantes', function (Blueprint $table) {
                $table->dropForeign(['proveedor_id']);
            });

            Schema::table('compras_lubricantes', function (Blueprint $table) {
                $table->renameColumn('proveedor_id', 'vendedor_id');
            });

            Schema::table('compras_lubricantes', function (Blueprint $table) {
                $table->foreign('vendedor_id')
                    ->references('id')
                    ->on('vendedores')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('proveedores') && ! Schema::hasTable('vendedores')) {
            Schema::rename('proveedores', 'vendedores');
        }
    }
};
