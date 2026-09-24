<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Renumerar los turnos existentes en orden cronológico para que numero_turno
        // sea un consecutivo global único, antes de reemplazar el índice único
        // compuesto (fecha, numero_turno) por uno único solo sobre numero_turno.
        DB::table('turnos')
            ->orderBy('fecha')
            ->orderBy('id')
            ->pluck('id')
            ->each(function (int $id, int $index): void {
                DB::table('turnos')->where('id', $id)->update(['numero_turno' => $index + 1]);
            });

        Schema::table('turnos', function (Blueprint $table) {
            $table->dropUnique(['fecha', 'numero_turno']);
            $table->unique('numero_turno');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            $table->dropUnique(['numero_turno']);
            $table->unique(['fecha', 'numero_turno']);
        });
    }
};
