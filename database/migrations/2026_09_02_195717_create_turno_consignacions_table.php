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
        Schema::create('turno_consignaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turno_id')->constrained('turnos')->cascadeOnDelete();
            $table->string('consignacion_no')->nullable();
            $table->decimal('valor', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->timestamps();
        });

        if (Schema::hasTable('turno_medios_pago')) {
            $rows = DB::table('turno_medios_pago')->get();
            $totals = $rows->groupBy('turno_id')->map(fn ($turnoRows) => $turnoRows->sum('consignacion_valor'));

            foreach ($rows as $row) {
                if ((float) $row->consignacion_valor === 0.0 && ! $row->consignacion_no) {
                    continue;
                }

                DB::table('turno_consignaciones')->insert([
                    'turno_id' => $row->turno_id,
                    'consignacion_no' => $row->consignacion_no,
                    'valor' => $row->consignacion_valor,
                    'total' => $totals[$row->turno_id],
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('turno_consignaciones');
    }
};
