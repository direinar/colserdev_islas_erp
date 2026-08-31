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
        Schema::table('turnos', function (Blueprint $table) {
            $table->decimal('lecturas_galones_corriente', 12, 3)->default(0);
            $table->decimal('lecturas_galones_acpm', 12, 3)->default(0);
            $table->decimal('lecturas_valor_corriente', 15, 2)->default(0);
            $table->decimal('lecturas_valor_acpm', 15, 2)->default(0);
            $table->decimal('total_venta_lecturas', 15, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            $table->dropColumn([
                'lecturas_galones_corriente',
                'lecturas_galones_acpm',
                'lecturas_valor_corriente',
                'lecturas_valor_acpm',
                'total_venta_lecturas',
            ]);
        });
    }
};
