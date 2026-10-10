<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Cuenta de descuentos compras (ingreso por mayorista) del comprobante contable de compras:
     * débito al proveedor de combustibles y crédito a esta cuenta por el margen mayorista.
     */
    public function up(): void
    {
        if (DB::table('comprobante_compras_cuentas')->where('clave', 'descuentos_compras')->exists()) {
            return;
        }

        DB::table('comprobante_compras_cuentas')->insert([
            'clave' => 'descuentos_compras',
            'descripcion' => 'Descuentos compras: margen mayorista (crédito)',
            'cuenta' => '421040',
            'proveedor_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('comprobante_compras_cuentas')->where('clave', 'descuentos_compras')->delete();
    }
};
