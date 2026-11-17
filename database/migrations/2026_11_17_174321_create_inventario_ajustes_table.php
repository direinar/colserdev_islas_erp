<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajustes de inventario fuera de compras y ventas: salidas por avería, pérdida, vencimiento o
     * consumo interno, y entradas por sobrante en un conteo físico. Van al kardex al costo promedio.
     */
    public function up(): void
    {
        Schema::create('inventario_ajustes', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->index();
            $table->string('inventario', 20);
            $table->string('referencia', 150)->default('');
            $table->string('motivo', 30);
            $table->decimal('unidades', 12, 3);
            $table->string('observacion', 500);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['inventario', 'referencia']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_ajustes');
    }
};
