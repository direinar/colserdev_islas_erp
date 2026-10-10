<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cuenta contable de cada banco (o caja) para el comprobante de recaudos de cartera.
     * Valores iniciales de la plantilla "INFORME DE RECAUDO DE CARTERA"; los bancos que se
     * creen después solo necesitan su cuenta.
     */
    public function up(): void
    {
        Schema::table('bancos', function (Blueprint $table) {
            $table->string('cuenta_contable', 30)->nullable()->after('name');
        });

        foreach (['CAJA' => '11050501', 'BANCOLOMBIA' => '11100510', 'DAVIVIENDA' => '11100502'] as $nombre => $cuenta) {
            DB::table('bancos')->where('name', $nombre)->update(['cuenta_contable' => $cuenta]);
        }
    }

    public function down(): void
    {
        Schema::table('bancos', function (Blueprint $table) {
            $table->dropColumn('cuenta_contable');
        });
    }
};
