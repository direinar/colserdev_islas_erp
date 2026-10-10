<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cuentas del comprobante contable de recaudos de cartera que no dependen del banco:
     * la caja de los recaudos por islas y la cartera de clientes.
     */
    public function up(): void
    {
        Schema::create('comprobante_recaudos_cuentas', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 60)->unique();
            $table->string('descripcion', 150);
            $table->string('cuenta', 30);
            $table->timestamps();
        });

        $ahora = now();

        DB::table('comprobante_recaudos_cuentas')->insert([
            ['clave' => 'recaudo_islas', 'descripcion' => 'Recaudo por islas: caja (débito)', 'cuenta' => '11050501', 'created_at' => $ahora, 'updated_at' => $ahora],
            ['clave' => 'cartera_clientes', 'descripcion' => 'Cartera clientes (crédito)', 'cuenta' => '13050501', 'created_at' => $ahora, 'updated_at' => $ahora],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('comprobante_recaudos_cuentas');
    }
};
