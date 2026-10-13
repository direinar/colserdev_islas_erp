<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Los recaudos y anticipos por islas salen de la línea de consignaciones y pagos
     * electrónicos y van en su propia línea del comprobante de ventas.
     */
    public function up(): void
    {
        DB::table('comprobante_ventas_cuentas')->insertOrIgnore([
            'clave' => 'recaudos_anticipos',
            'descripcion' => 'Recaudos y anticipos por islas',
            'cuenta' => '11050501',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('comprobante_ventas_cuentas')->where('clave', 'recaudos_anticipos')->delete();
    }
};
