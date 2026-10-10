<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cuentas contables del comprobante de ventas del periodo (Informe Control Caja).
     * Son susceptibles de cambiar, por eso el administrador las edita. Valores iniciales
     * tomados de la plantilla "Informe CONTROL DIARIO".
     */
    public function up(): void
    {
        Schema::create('comprobante_ventas_cuentas', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 60)->unique();
            $table->string('descripcion', 150);
            $table->string('cuenta', 30);
            $table->timestamps();
        });

        $ahora = now();

        DB::table('comprobante_ventas_cuentas')->insert(array_map(
            fn (array $fila): array => $fila + ['created_at' => $ahora, 'updated_at' => $ahora],
            [
                ['clave' => 'consignaciones_pagos', 'descripcion' => 'Consignaciones, TC, QR y transferencias', 'cuenta' => '11050501'],
                ['clave' => 'descuentos_ventas', 'descripcion' => 'Descuentos en ventas (puntos redimidos + descuentos)', 'cuenta' => '413601'],
                ['clave' => 'gastos_gasolina_eds', 'descripcion' => 'Gastos de gasolina EDS', 'cuenta' => '519535'],
                ['clave' => 'varios', 'descripcion' => 'Varios', 'cuenta' => '11050501'],
                ['clave' => 'faltantes_sobrantes', 'descripcion' => 'Faltantes / sobrantes', 'cuenta' => '11050501'],
                ['clave' => 'cartera_clientes', 'descripcion' => 'Cartera clientes (ventas a crédito / recaudos y anticipos)', 'cuenta' => '13050501'],
                ['clave' => 'ventas_combustible', 'descripcion' => 'Ventas combustible', 'cuenta' => '41350701'],
                ['clave' => 'costo_ventas_combustible', 'descripcion' => 'Costo de ventas combustible', 'cuenta' => '41350801'],
                ['clave' => 'inventario_combustibles', 'descripcion' => 'Inventarios de combustibles', 'cuenta' => '143508'],
                ['clave' => 'ventas_canastilla', 'descripcion' => 'Ventas de canastilla', 'cuenta' => '41351001'],
                ['clave' => 'costo_ventas_canastilla', 'descripcion' => 'Costo de ventas canastilla', 'cuenta' => '41351002'],
                ['clave' => 'inventario_canastilla', 'descripcion' => 'Inventarios de canastilla', 'cuenta' => '143509'],
                ['clave' => 'iva_por_pagar', 'descripcion' => 'IVA por pagar', 'cuenta' => '23670501'],
            ],
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('comprobante_ventas_cuentas');
    }
};
