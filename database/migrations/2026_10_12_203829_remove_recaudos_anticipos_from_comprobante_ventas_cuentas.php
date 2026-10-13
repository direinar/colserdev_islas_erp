<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Los recaudos y anticipos por islas ya no van en el comprobante de ventas (su asiento
     * está en el de Recaudos de cartera), así que su cuenta deja de usarse.
     */
    public function up(): void
    {
        DB::table('comprobante_ventas_cuentas')->where('clave', 'recaudos_anticipos')->delete();
    }

    public function down(): void
    {
        DB::table('comprobante_ventas_cuentas')->insertOrIgnore([
            'clave' => 'recaudos_anticipos',
            'descripcion' => 'Recaudos y anticipos por islas',
            'cuenta' => '11050501',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
