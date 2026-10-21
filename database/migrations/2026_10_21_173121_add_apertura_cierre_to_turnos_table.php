<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Apertura y cierre de la planilla: el islero abre el turno con sus mangueras (una
     * manguera solo puede estar en una planilla abierta) y lo cierra con las lecturas
     * finales. Las planillas ya registradas quedan cerradas; su turno del día se numera
     * en el orden del consecutivo dentro de cada fecha.
     */
    public function up(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            $table->string('estado', 10)->default('cerrada')->after('numero_turno');
            $table->unsignedTinyInteger('turno_dia')->nullable()->after('estado');
            $table->time('hora_inicio')->nullable()->after('turno_dia');
            $table->timestamp('cerrado_at')->nullable()->after('hora_inicio');
            $table->string('cerrado_por')->nullable()->after('cerrado_at');
        });

        // Lectura final pendiente (turno abierto) = null, distinta de una lectura en cero.
        Schema::table('turno_surtidores', function (Blueprint $table) {
            $table->decimal('lectura_final', 12, 3)->nullable()->default(null)->change();
        });

        $porFecha = [];
        DB::table('turnos')->select(['id', 'fecha'])->orderBy('numero_turno')->get()
            ->each(function (object $turno) use (&$porFecha): void {
                $fecha = substr((string) $turno->fecha, 0, 10);
                $porFecha[$fecha] = ($porFecha[$fecha] ?? 0) + 1;
                DB::table('turnos')->where('id', $turno->id)->update(['turno_dia' => min($porFecha[$fecha], 255)]);
            });
    }

    public function down(): void
    {
        DB::table('turno_surtidores')->whereNull('lectura_final')->update(['lectura_final' => 0]);

        Schema::table('turno_surtidores', function (Blueprint $table) {
            $table->decimal('lectura_final', 12, 3)->default(0)->change();
        });

        Schema::table('turnos', function (Blueprint $table) {
            $table->dropColumn(['estado', 'turno_dia', 'hora_inicio', 'cerrado_at', 'cerrado_por']);
        });
    }
};
