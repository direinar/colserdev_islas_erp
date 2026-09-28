<?php

namespace App\Services;

use App\Models\CarteraMovimiento;
use App\Models\Turno;
use Illuminate\Support\Collection;

/**
 * Lleva a la cartera del cliente los movimientos de la planilla de turno:
 * - CARTERA - CRÉDITO DIRECTO: cargo "Venta a crédito".
 * - RECAUDOS Y ANTICIPOS POR ISLAS: abono "Recaudo por islas".
 * - RECAUDOS POR ADMINISTRACIÓN: abono "Recaudo por administración"
 *   (el cliente es el de la columna CLIENTE; BANCO/CAJA no va a cartera).
 *
 * La planilla es la fuente de verdad de planilla (número de turno), fecha,
 * factura y valores. Los datos del vehículo que se completan después en
 * cartera (placas, producto, galones...) se conservan mientras la factura y
 * el cliente del renglón no cambien.
 */
class CarteraTurnoSyncService
{
    public const CONCEPTO_VENTA_CREDITO = 'Venta a crédito';

    public const CONCEPTO_RECAUDO_ISLAS = 'Recaudo por islas';

    public const CONCEPTO_RECAUDO_ADMIN = 'Recaudo por administración';

    public function __construct(private CarteraSaldoService $saldos) {}

    /**
     * Debe llamarse después de guardar todas las tablas del turno. Lee los
     * renglones desde la base de datos: RECAUDOS POR ADMINISTRACIÓN no se
     * reemplaza cuando guarda un islero y aun así debe quedar en cartera.
     */
    public function sincronizar(Turno $turno): void
    {
        // load() y no loadMissing(): los renglones se acaban de recrear en TurnoController::store.
        $turno->load(['cartera.cliente', 'recaudos', 'recaudosAdmin']);

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

            $movimiento = $this->pendiente($existentes, $conservados, CarteraMovimiento::ORIGEN_CREDITO_DIRECTO, (int) $renglon->cliente_id, $factura);

            $datosPlanilla = [
                'planillas' => (string) $turno->numero_turno,
                'fecha' => $turno->fecha->toDateString(),
                'factura' => $factura,
                'vr_neto_cargo' => $valor,
                'abonos' => 0,
                'concepto' => self::CONCEPTO_VENTA_CREDITO,
            ];

            if ($movimiento) {
                $movimiento->update($datosPlanilla);
            } else {
                $movimiento = CarteraMovimiento::create($datosPlanilla + [
                    'customer_id' => $renglon->cliente_id,
                    'turno_id' => $turno->id,
                    'origen' => CarteraMovimiento::ORIGEN_CREDITO_DIRECTO,
                    'bruto' => 0,
                    'descuento' => 0,
                    'tercero' => (string) ($renglon->cliente?->name ?? ''),
                    'nit' => (string) ($renglon->cliente?->document ?? ''),
                ]);
            }

            $conservados[] = $movimiento->id;
        }

        $abonos = [
            [CarteraMovimiento::ORIGEN_RECAUDO_ISLAS, self::CONCEPTO_RECAUDO_ISLAS, $turno->recaudos, 'cliente_id'],
            [CarteraMovimiento::ORIGEN_RECAUDO_ADMIN, self::CONCEPTO_RECAUDO_ADMIN, $turno->recaudosAdmin, 'responsable_id'],
        ];

        $clientesActuales = $turno->cartera->pluck('cliente_id');

        foreach ($abonos as [$origen, $concepto, $renglones, $campoCliente]) {
            foreach ($renglones as $renglon) {
                $customerId = (int) $renglon->{$campoCliente};
                $valor = round((float) $renglon->valor, 0);

                if (! $customerId || $valor <= 0) {
                    continue;
                }

                $clientesActuales->push($customerId);

                $movimiento = $this->pendiente($existentes, $conservados, $origen, $customerId);

                $datosPlanilla = [
                    'planillas' => (string) $turno->numero_turno,
                    'fecha' => $turno->fecha->toDateString(),
                    'factura' => '',
                    'bruto' => 0,
                    'vr_neto_cargo' => 0,
                    'abonos' => $valor,
                    'concepto' => $concepto,
                ];

                if ($movimiento) {
                    $movimiento->update($datosPlanilla);
                } else {
                    $movimiento = CarteraMovimiento::create($datosPlanilla + [
                        'customer_id' => $customerId,
                        'turno_id' => $turno->id,
                        'origen' => $origen,
                    ]);
                }

                $conservados[] = $movimiento->id;
            }
        }

        // Renglones que ya no están en la planilla (o cambiaron de cliente/factura).
        CarteraMovimiento::query()
            ->where('turno_id', $turno->id)
            ->whereNotIn('id', $conservados)
            ->delete();

        // Clientes afectados: los actuales del turno y los que se quitaron de la planilla.
        $existentes->pluck('customer_id')
            ->merge($clientesActuales)
            ->filter()
            ->unique()
            ->each(fn ($customerId) => $this->saldos->recalcular((int) $customerId));
    }

    /**
     * Movimiento ya registrado del mismo origen y cliente (y factura, en los
     * créditos) que todavía no se asignó a un renglón de la planilla.
     *
     * @param  Collection<int, CarteraMovimiento>  $existentes
     * @param  array<int, int>  $conservados
     */
    private function pendiente(Collection $existentes, array $conservados, string $origen, int $customerId, ?string $factura = null): ?CarteraMovimiento
    {
        return $existentes->first(fn (CarteraMovimiento $m): bool => ! in_array($m->id, $conservados, true)
            && $m->origen === $origen
            && (int) $m->customer_id === $customerId
            && ($factura === null || $m->factura === $factura));
    }
}
