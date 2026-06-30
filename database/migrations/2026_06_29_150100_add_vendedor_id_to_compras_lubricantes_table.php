<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compras_lubricantes', function (Blueprint $table) {
            $table->foreignId('vendedor_id')
                ->nullable()
                ->after('fecha')
                ->constrained('vendedores')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('compras_lubricantes', function (Blueprint $table) {
            $table->dropForeign(['vendedor_id']);
            $table->dropColumn('vendedor_id');
        });
    }
};
