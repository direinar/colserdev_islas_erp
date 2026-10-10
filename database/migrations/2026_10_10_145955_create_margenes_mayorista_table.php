<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Parámetros del convenio de mayorista con su fecha de vigencia: porcentaje y margen
     * por galón. Margen mayorista = galones comprados x porcentaje x margen. Cada factura
     * se liquida con los parámetros vigentes en su fecha.
     */
    public function up(): void
    {
        Schema::create('margenes_mayorista', function (Blueprint $table) {
            $table->id();
            $table->date('vigente_desde')->unique();
            $table->decimal('porcentaje', 8, 4);
            $table->decimal('margen', 12, 2);
            $table->timestamps();
        });

        DB::table('margenes_mayorista')->insert([
            'vigente_desde' => '2026-01-01',
            'porcentaje' => 0.3,
            'margen' => 585.71,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('margenes_mayorista');
    }
};
