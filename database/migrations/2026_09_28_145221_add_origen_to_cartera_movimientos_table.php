<?php

use App\Models\CarteraMovimiento;
use App\Models\Turno;
use App\Services\CarteraTurnoSyncService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Origen de cada movimiento de cartera: créditos directos y recaudos
     * (islas / administración) de la planilla de turno, o saldo inicial
     * cargado al arrancar el sistema. Los movimientos manuales eran datos de
     * prueba y se eliminan; luego se cargan los recaudos ya registrados.
     */
    public function up(): void
    {
        Schema::table('cartera_movimientos', function (Blueprint $table) {
            $table->string('origen', 30)->nullable()->after('turno_id');
            $table->index(['customer_id', 'origen']);
        });

        DB::table('cartera_movimientos')
            ->whereNotNull('turno_id')
            ->update(['origen' => CarteraMovimiento::ORIGEN_CREDITO_DIRECTO]);

        DB::table('cartera_movimientos')->whereNull('turno_id')->delete();

        $sync = app(CarteraTurnoSyncService::class);

        Turno::query()->orderBy('id')->each(fn (Turno $turno) => $sync->sincronizar($turno));
    }

    public function down(): void
    {
        DB::table('cartera_movimientos')
            ->whereIn('origen', [
                CarteraMovimiento::ORIGEN_RECAUDO_ISLAS,
                CarteraMovimiento::ORIGEN_RECAUDO_ADMIN,
                CarteraMovimiento::ORIGEN_SALDO_INICIAL,
            ])
            ->delete();

        Schema::table('cartera_movimientos', function (Blueprint $table) {
            $table->dropIndex(['customer_id', 'origen']);
            $table->dropColumn('origen');
        });
    }
};
