<?php

namespace App\Services;

use App\Models\CarteraMovimiento;
use App\Models\Turno;

/**
 * Lleva los créditos directos de la planilla de turno (tabla CARTERA -
 * CRÉDITO DIRECTO) al módulo de cartera del cliente.
 *
 * La planilla es la fuente de verdad de planilla (número de turno), fecha,
 * factura y valor. Los datos que se completan después en el módulo de
 * cartera (placas, producto, abonos, cuenta...) se conservan mientras la
 * factura y el cliente del renglón no cambien.
 */
class CarteraTurnoSyncService
{
    public const CONCEPTO_VENTA_CREDITO = 'Venta a crédito';

    public function __construct(private CarteraSaldoService $saldos) {}

    public function sincronizar(Turno $turno): void
    {
        // load() y no loadMissing(): los renglones se acaban de recrear en TurnoController::store.
        $turno->load('cartera.cliente');

        $existentes = CarteraMovimiento::query()
            ->where('turno_id', $turno->id)
            ->orderBy('id')
            ->get();

        $conservados = [];

        foreach ($turno->cartera as $renglon) {
            if (! $renglon->cliente_id) {
                continue;
            }

            $factura = trim((string) ($renglon->factura_no ?? ''));
            $valor = round((float) $renglon->valor, 0);

            $movimiento = $existentes->first(fn (CarteraMovimiento $m): bool => ! in_array($m->id, $conservados, true)
                && (int) $m->customer_id === (int) $renglon->cliente_id
                && $m->factura === $factura);

            $datosPlanilla = [
                'planillas' => (string) $turno->numero_turno,
                'fecha' => $turno->fecha->toDateString(),
                'factura' => $factura,
                'bruto' => $valor,
                'descuento' => 0,
                'vr_neto_cargo' => $valor,
                'concepto' => self::CONCEPTO_VENTA_CREDITO,
            ];

            if ($movimiento) {
                $movimiento->update($datosPlanilla);
            } else {
                $movimiento = CarteraMovimiento::create($datosPlanilla + [
                    'customer_id' => $renglon->cliente_id,
                    'turno_id' => $turno->id,
                    'tercero' => (string) ($renglon->cliente?->name ?? ''),
                    'nit' => (string) ($renglon->cliente?->document ?? ''),
                ]);
            }

            $conservados[] = $movimiento->id;
        }

        // Renglones que ya no están en la planilla (o cambiaron de cliente/factura).
        CarteraMovimiento::query()
            ->where('turno_id', $turno->id)
            ->whereNotIn('id', $conservados)
            ->delete();

        // Clientes afectados: los actuales del turno y los que se quitaron de la planilla.
        $existentes->pluck('customer_id')
            ->merge($turno->cartera->pluck('cliente_id'))
            ->filter()
            ->unique()
            ->each(fn ($customerId) => $this->saldos->recalcular((int) $customerId));
    }
}
