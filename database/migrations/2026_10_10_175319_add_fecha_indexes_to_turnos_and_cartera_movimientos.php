<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los informes filtran turnos por fecha y la cartera por cliente y fecha (saldos, estado de
     * cuenta). turnos.fecha se quedó sin índice cuando el único (fecha, numero_turno) pasó a ser
     * solo numero_turno.
     */
    public function up(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            $table->index('fecha');
        });

        Schema::table('cartera_movimientos', function (Blueprint $table) {
            $table->index(['customer_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::table('cartera_movimientos', function (Blueprint $table) {
            $table->dropIndex(['customer_id', 'fecha']);
        });

        Schema::table('turnos', function (Blueprint $table) {
            $table->dropIndex(['fecha']);
        });
    }
};
