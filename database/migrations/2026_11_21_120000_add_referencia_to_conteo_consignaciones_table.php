<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * No. alfanumérico de los registros manuales de consignaciones internas (p. ej. "R-2550").
     * El consecutivo sigue en la columna numérica `numero`.
     */
    public function up(): void
    {
        Schema::table('conteo_consignaciones', function (Blueprint $table) {
            $table->string('referencia', 40)->nullable()->after('numero');
        });

        DB::table('conteo_consignaciones')->where('manual', true)->whereNotNull('numero')
            ->update(['referencia' => DB::raw('CAST(numero AS CHAR)')]);
    }

    public function down(): void
    {
        Schema::table('conteo_consignaciones', function (Blueprint $table) {
            $table->dropColumn('referencia');
        });
    }
};
