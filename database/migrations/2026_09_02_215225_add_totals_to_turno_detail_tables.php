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
        foreach (['turno_ventas', 'turno_surtidores', 'turno_recaudos', 'turno_gasolina_eds', 'turno_varios', 'turno_recaudos_admin'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->decimal('total', 15, 2)->default(0)->after($tableName === 'turno_surtidores' ? 'galones' : 'valor');
            });
        }

        Schema::table('turno_transferencias', function (Blueprint $table): void {
            $table->decimal('total', 15, 2)->default(0)->after('puntos_redimidos');
            $table->decimal('total_puntos', 15, 2)->default(0)->after('total');
        });

        $this->backfillTotal('turno_ventas', 'valor');
        $this->backfillTotal('turno_surtidores', 'galones');
        $this->backfillTotal('turno_recaudos', 'valor');
        $this->backfillTotal('turno_gasolina_eds', 'valor');
        $this->backfillTotal('turno_varios', 'valor');
        $this->backfillTotal('turno_recaudos_admin', 'valor');
        $this->backfillTotal('turno_transferencias', 'valor', 'puntos_redimidos');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['turno_ventas', 'turno_surtidores', 'turno_recaudos', 'turno_gasolina_eds', 'turno_varios', 'turno_recaudos_admin'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn('total');
            });
        }

        Schema::table('turno_transferencias', function (Blueprint $table): void {
            $table->dropColumn(['total', 'total_puntos']);
        });
    }

    private function backfillTotal(string $tableName, string $valueColumn, ?string $secondaryColumn = null): void
    {
        DB::table($tableName)
            ->distinct()
            ->pluck('turno_id')
            ->each(function (int|string $turnoId) use ($tableName, $valueColumn, $secondaryColumn): void {
                $query = DB::table($tableName)->where('turno_id', $turnoId);
                $total = $query->sum($valueColumn);
                $updates = ['total' => $total];

                if ($secondaryColumn) {
                    $updates['total_puntos'] = $query->sum($secondaryColumn);
                }

                DB::table($tableName)
                    ->where('turno_id', $turnoId)
                    ->update($updates);
            });
    }
};
