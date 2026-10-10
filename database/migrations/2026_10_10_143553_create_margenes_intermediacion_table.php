<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Margen de intermediación de combustibles (pesos por galón) con su fecha de vigencia.
     * Cada mes se liquida con el margen vigente el primer día del mes: por eso enero de
     * 2026 sigue con el margen de 2025 (1.099,05) y desde febrero aplica el de 2026.
     */
    public function up(): void
    {
        Schema::create('margenes_intermediacion', function (Blueprint $table) {
            $table->id();
            $table->date('vigente_desde')->unique();
            $table->decimal('valor', 12, 2);
            $table->timestamps();
        });

        $ahora = now();

        DB::table('margenes_intermediacion')->insert([
            ['vigente_desde' => '2025-01-01', 'valor' => 1099.05, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['vigente_desde' => '2026-02-01', 'valor' => 1155.10, 'created_at' => $ahora, 'updated_at' => $ahora],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('margenes_intermediacion');
    }
};
