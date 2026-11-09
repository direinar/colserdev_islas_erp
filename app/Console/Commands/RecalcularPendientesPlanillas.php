<?php

namespace App\Console\Commands;

use App\Models\Turno;
use App\Services\CuadreTurnoService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Recalcula el sobrante/faltante guardado de las planillas pendientes de revisión que quedaron
 * con $1 o $2 por el redondeo de centavos ya corregido (las ventas se redondean por fila). Solo
 * cambia ese campo y solo donde el cálculo actual da $0. Sin --aplicar solo muestra la lista.
 */
#[Signature('planillas:recalcular-pendientes {--aplicar : Guarda el valor recalculado (sin esta opción solo muestra la lista)}')]
#[Description('Recalcula el sobrante/faltante de las planillas pendientes que solo tienen el $1 del redondeo')]
class RecalcularPendientesPlanillas extends Command
{
    public function handle(CuadreTurnoService $cuadre): int
    {
        $planillas = Turno::query()
            ->noAnulados()
            ->where('revisado', false)
            ->orderBy('numero_turno')
            ->get()
            ->filter(fn (Turno $turno): bool => abs((float) $turno->sobrante_pendiente) >= 1 && abs($cuadre->pendiente($turno)) < 1);

        if ($planillas->isEmpty()) {
            $this->info('No hay planillas con el sobrante/faltante del redondeo.');

            return self::SUCCESS;
        }

        $this->table(['Turno', 'Fecha', 'Guardado', 'Recalculado'], $planillas->map(fn (Turno $turno): array => [
            $turno->numero_turno,
            $turno->fecha->format('d/m/Y'),
            (float) $turno->sobrante_pendiente,
            $cuadre->pendiente($turno),
        ])->all());

        if (! $this->option('aplicar')) {
            $this->warn($planillas->count().' planilla(s). Nada cambió: ejecute con --aplicar para guardarlo.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($planillas, $cuadre): void {
            foreach ($planillas as $turno) {
                // Sin tocar updated_at: no es una edición de la planilla.
                $turno->timestamps = false;
                $turno->update(['sobrante_pendiente' => $cuadre->pendiente($turno)]);
            }
        });

        $this->info($planillas->count().' planilla(s) recalculadas: ya se pueden revisar.');

        return self::SUCCESS;
    }
}
