<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Consignaciones internas del conteo:
     * - Cambio de talonario: el consecutivo llega hasta talonario_fin y sigue desde
     *   talonario_inicio (los números intermedios no existen).
     * - Filas manuales: No. opcional, valor y concepto, que suman al total y se conservan
     *   al volver a traer los valores de las planillas.
     */
    public function up(): void
    {
        Schema::table('conteos', function (Blueprint $table) {
            $table->unsignedBigInteger('talonario_fin')->nullable()->after('ultima_consignacion');
            $table->unsignedBigInteger('talonario_inicio')->nullable()->after('talonario_fin');
        });

        Schema::table('conteo_consignaciones', function (Blueprint $table) {
            $table->unsignedBigInteger('numero')->nullable()->change();
            $table->boolean('manual')->default(false)->after('turno_id');
            $table->string('concepto', 200)->nullable()->after('manual');
        });
    }

    public function down(): void
    {
        Schema::table('conteo_consignaciones', function (Blueprint $table) {
            $table->dropColumn(['manual', 'concepto']);
        });

        Schema::table('conteos', function (Blueprint $table) {
            $table->dropColumn(['talonario_fin', 'talonario_inicio']);
        });
    }
};
