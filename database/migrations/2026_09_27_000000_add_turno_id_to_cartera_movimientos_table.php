<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Liga cada movimiento de cartera con la planilla de turno que lo originó
     * (tabla CARTERA - CRÉDITO DIRECTO). turno_id nulo = movimiento manual.
     */
    public function up(): void
    {
        Schema::table('cartera_movimientos', function (Blueprint $table) {
            $table->foreignId('turno_id')->nullable()->after('customer_id')
                ->constrained('turnos')->cascadeOnDelete();
        });

        // Carga los créditos directos ya registrados en planillas anteriores.
        $rows = DB::table('turno_carteras')
            ->join('turnos', 'turnos.id', '=', 'turno_carteras.turno_id')
            ->join('customers', 'customers.id', '=', 'turno_carteras.cliente_id')
            ->select([
                'turno_carteras.turno_id',
                'turno_carteras.cliente_id',
                'turno_carteras.factura_no',
                'turno_carteras.valor',
                'turnos.numero_turno',
                'turnos.fecha',
                'customers.name as customer_name',
                'customers.document as customer_document',
            ])
            ->orderBy('turno_carteras.id')
            ->get();

        foreach ($rows as $row) {
            $valor = round((float) $row->valor, 0);

            DB::table('cartera_movimientos')->insert([
                'customer_id' => $row->cliente_id,
                'turno_id' => $row->turno_id,
                'planillas' => (string) $row->numero_turno,
                'fecha' => substr((string) $row->fecha, 0, 10),
                'factura' => (string) ($row->factura_no ?? ''),
                'bruto' => $valor,
                'vr_neto_cargo' => $valor,
                'tercero' => (string) ($row->customer_name ?? ''),
                'nit' => (string) ($row->customer_document ?? ''),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Saldo acumulado por cliente (cargos - abonos) en orden cronológico.
        foreach ($rows->pluck('cliente_id')->unique() as $customerId) {
            $saldo = 0.0;

            $movimientos = DB::table('cartera_movimientos')
                ->where('customer_id', $customerId)
                ->get()
                ->sortBy([
                    fn ($a, $b) => strcmp((string) $a->fecha, (string) $b->fecha),
                    fn ($a, $b) => (int) $a->planillas <=> (int) $b->planillas,
                    fn ($a, $b) => $a->id <=> $b->id,
                ]);

            foreach ($movimientos as $movimiento) {
                $saldo += (float) $movimiento->vr_neto_cargo - (float) $movimiento->abonos;
                DB::table('cartera_movimientos')->where('id', $movimiento->id)->update(['saldo' => $saldo]);
            }
        }
    }

    public function down(): void
    {
        DB::table('cartera_movimientos')->whereNotNull('turno_id')->delete();

        Schema::table('cartera_movimientos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('turno_id');
        });
    }
};
