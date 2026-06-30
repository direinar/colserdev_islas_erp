<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cartera_movimientos', function (Blueprint $table) {
            $table->decimal('saldo_inicial', 15, 0)->default(0)->after('customer_id');
            $table->string('planillas', 40)->default('')->after('fecha_final');
            $table->date('fecha')->nullable()->after('planillas');
            $table->string('factura', 50)->default('')->after('fecha');
            $table->string('placas', 40)->default('')->after('factura');
            $table->string('producto', 60)->default('')->after('placas');
            $table->decimal('galones', 12, 3)->default(0)->after('producto');
            $table->decimal('vr_unitario', 15, 0)->default(0)->after('galones');
            $table->decimal('bruto', 15, 0)->default(0)->after('vr_unitario');
            $table->decimal('descuento', 15, 0)->default(0)->after('bruto');
            $table->decimal('vr_neto_cargo', 15, 0)->default(0)->after('descuento');
            $table->decimal('abonos', 15, 0)->default(0)->after('vr_neto_cargo');
            $table->decimal('saldo', 15, 0)->default(0)->after('abonos');
        });
    }

    public function down(): void
    {
        Schema::table('cartera_movimientos', function (Blueprint $table) {
            $table->dropColumn([
                'saldo_inicial',
                'planillas',
                'fecha',
                'factura',
                'placas',
                'producto',
                'galones',
                'vr_unitario',
                'bruto',
                'descuento',
                'vr_neto_cargo',
                'abonos',
                'saldo',
            ]);
        });
    }
};
