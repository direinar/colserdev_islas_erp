<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('turno_medios_pago', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turno_id')->constrained('turnos')->cascadeOnDelete();
            $table->string('consignacion_no')->nullable();
            $table->decimal('consignacion_valor', 15, 2)->default(0);
            $table->decimal('descuento', 15, 2)->default(0);
            $table->string('cartera_factura_no')->nullable();
            $table->foreignId('cliente_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->decimal('cartera_valor', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('turno_medios_pago');
    }
};
