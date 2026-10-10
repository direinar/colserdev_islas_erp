<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cuentas contables del comprobante contable de compras. Son susceptibles de
     * cambiar, por eso viven en una tabla que el administrador edita. La cuenta del
     * proveedor de combustibles guarda además el tercero (las compras de combustible
     * no registran proveedor). Valores iniciales tomados de la plantilla de Excel.
     */
    public function up(): void
    {
        Schema::create('comprobante_compras_cuentas', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 60)->unique();
            $table->string('descripcion', 150);
            $table->string('cuenta', 30);
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $table->timestamps();
        });

        $chevron = DB::table('proveedores')->where('name', 'like', '%CHEVRON%')->value('id');
        $ahora = now();

        DB::table('comprobante_compras_cuentas')->insert([
            ['clave' => 'inventario_combustibles', 'descripcion' => 'Inventario de combustibles (débito)', 'cuenta' => '143508', 'proveedor_id' => null],
            ['clave' => 'proveedor_combustibles', 'descripcion' => 'Proveedor de combustibles (crédito)', 'cuenta' => '230505', 'proveedor_id' => $chevron],
            ['clave' => 'inventario_canastilla', 'descripcion' => 'Inventario de canastilla, valor sin IVA (débito)', 'cuenta' => '143509', 'proveedor_id' => null],
            ['clave' => 'iva_canastilla', 'descripcion' => 'IVA de compras de canastilla (débito)', 'cuenta' => '23670506', 'proveedor_id' => null],
            ['clave' => 'proveedor_canastilla', 'descripcion' => 'Proveedores de canastilla, total con IVA (crédito)', 'cuenta' => '233501', 'proveedor_id' => null],
        ]);

        DB::table('comprobante_compras_cuentas')->update(['created_at' => $ahora, 'updated_at' => $ahora]);
    }

    public function down(): void
    {
        Schema::dropIfExists('comprobante_compras_cuentas');
    }
};
