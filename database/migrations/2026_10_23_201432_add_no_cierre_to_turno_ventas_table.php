<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Número del cierre de IAPROPIADA de cada fila de ventas: un surtidor puede tener
     * varios cierres en el turno.
     */
    public function up(): void
    {
        Schema::table('turno_ventas', function (Blueprint $table) {
            $table->string('no_cierre', 30)->nullable()->after('surtidor');
        });
    }

    public function down(): void
    {
        Schema::table('turno_ventas', function (Blueprint $table) {
            $table->dropColumn('no_cierre');
        });
    }
};
