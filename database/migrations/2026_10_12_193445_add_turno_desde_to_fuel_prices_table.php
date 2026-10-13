<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El precio rige desde un número de turno (consecutivo global), no desde una fecha:
     * en un mismo día el precio puede cambiar entre un turno y otro.
     */
    public function up(): void
    {
        Schema::table('fuel_prices', function (Blueprint $table) {
            $table->unsignedInteger('turno_desde')->nullable()->after('price');
        });

        // Los precios existentes rigen desde el primer turno de su fecha de vigencia.
        foreach (DB::table('fuel_prices')->get(['id', 'effective_date']) as $precio) {
            $ultimoTurnoAnterior = $precio->effective_date
                ? (int) DB::table('turnos')->whereDate('fecha', '<', $precio->effective_date)->max('numero_turno')
                : 0;

            DB::table('fuel_prices')->where('id', $precio->id)->update(['turno_desde' => $ultimoTurnoAnterior + 1]);
        }
    }

    public function down(): void
    {
        Schema::table('fuel_prices', function (Blueprint $table) {
            $table->dropColumn('turno_desde');
        });
    }
};
