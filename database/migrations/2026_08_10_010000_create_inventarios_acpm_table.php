<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventarios_acpm', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->nullable();
            $table->unsignedInteger('planilla_no')->nullable();
            $table->string('fc_compra_no', 80)->nullable();
            $table->string('proveedor', 160)->nullable();

            $table->decimal('entradas_galones', 12, 3)->default(0);
            $table->decimal('salidas_galones', 12, 3)->default(0);
            $table->decimal('saldo_galones', 12, 3)->default(0);

            $table->decimal('valor_entradas', 15, 2)->default(0);
            $table->decimal('valor_salidas', 15, 2)->default(0);
            $table->decimal('valor_saldo', 15, 2)->default(0);
            $table->decimal('costo_promedio', 15, 4)->default(0);

            $table->decimal('vr_venta', 15, 2)->default(0);
            $table->decimal('precio_venta', 15, 2)->default(0);

            $table->decimal('saldo_anterior_galones', 12, 3)->default(0);
            $table->decimal('saldo_anterior_valor', 15, 2)->default(0);
            $table->decimal('saldo_anterior_promedio', 15, 4)->default(0);

            $table->timestamps();

            $table->index(['fecha']);
            $table->index(['planilla_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventarios_acpm');
    }
};
