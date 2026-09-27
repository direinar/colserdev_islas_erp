<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Los movimientos que vienen de la planilla de turno (CARTERA - CRÉDITO
     * DIRECTO) son ventas a crédito.
     */
    public function up(): void
    {
        DB::table('cartera_movimientos')
            ->whereNotNull('turno_id')
            ->update(['concepto' => 'Venta a crédito']);
    }

    public function down(): void
    {
        DB::table('cartera_movimientos')
            ->whereNotNull('turno_id')
            ->where('concepto', 'Venta a crédito')
            ->update(['concepto' => '']);
    }
};
