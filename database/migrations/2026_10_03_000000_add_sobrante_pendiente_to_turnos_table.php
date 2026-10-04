<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SOBRANTE o FALTANTE según cierres de IAPROPIADA que quedó sin trasladar
     * al guardar la planilla (lo calcula el navegador). Null = la planilla no
     * se ha guardado desde que existe el campo. Si no es cero, la planilla no
     * se puede marcar como revisada.
     */
    public function up(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            $table->decimal('sobrante_pendiente', 14, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            $table->dropColumn('sobrante_pendiente');
        });
    }
};
