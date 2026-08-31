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
            $table->dropColumn([
                'tirillas_galones_corriente',
                'tirillas_galones_acpm',
                'tirillas_valor_corriente',
                'tirillas_valor_acpm',
                'total_ventas',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            $table->decimal('tirillas_galones_corriente', 12, 3)->default(0);
            $table->decimal('tirillas_galones_acpm', 12, 3)->default(0);
            $table->decimal('tirillas_valor_corriente', 15, 2)->default(0);
            $table->decimal('tirillas_valor_acpm', 15, 2)->default(0);
            $table->decimal('total_ventas', 15, 2)->default(0);
        });
    }
};
