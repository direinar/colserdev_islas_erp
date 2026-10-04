<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Observación del cliente: el concepto de los abonos de RECAUDOS POR
     * ADMINISTRACIÓN se muestra abreviado como "Recaudo por Admón.".
     */
    public function up(): void
    {
        DB::table('cartera_movimientos')
            ->where('origen', 'recaudo_admin')
            ->update(['concepto' => 'Recaudo por Admón.']);
    }

    public function down(): void
    {
        DB::table('cartera_movimientos')
            ->where('origen', 'recaudo_admin')
            ->update(['concepto' => 'Recaudo por administración']);
    }
};
