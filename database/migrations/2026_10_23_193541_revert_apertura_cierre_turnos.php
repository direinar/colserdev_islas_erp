<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Se vuelve a la planilla anterior: se quita la apertura/cierre por mangueras
     * (2026_10_21_173121) y las correcciones y cambios de contador (2026_10_21_175039).
     * Producción no permite migrate:rollback, por eso se deshace con una migración nueva;
     * en una base nueva esas columnas y tablas no existen y no hace nada.
     */
    public function up(): void
    {
        Schema::dropIfExists('lecturas_correcciones');

        if (Schema::hasColumn('turno_surtidores', 'contador_retiro')) {
            Schema::table('turno_surtidores', function (Blueprint $table) {
                $table->dropColumn(['contador_retiro', 'contador_instalacion']);
            });
        }

        DB::table('turno_surtidores')->whereNull('lectura_final')->update(['lectura_final' => 0]);

        Schema::table('turno_surtidores', function (Blueprint $table) {
            $table->decimal('lectura_final', 12, 3)->default(0)->change();
        });

        if (Schema::hasColumn('turnos', 'estado')) {
            Schema::table('turnos', function (Blueprint $table) {
                $table->dropColumn(['estado', 'turno_dia', 'hora_inicio', 'cerrado_at', 'cerrado_por']);
            });
        }

        DB::table('migrations')->whereIn('migration', [
            '2026_10_21_173121_add_apertura_cierre_to_turnos_table',
            '2026_10_21_175039_create_lecturas_correcciones_table',
        ])->delete();
    }

    public function down(): void
    {
        // La planilla por mangueras se retiró; no se vuelve a crear.
    }
};
