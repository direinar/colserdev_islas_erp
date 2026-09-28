<?php

namespace App\Services;

use App\Models\CarteraMovimiento;
use Illuminate\Support\Collection;

/**
 * Saldos de la cartera de un cliente: saldo = cargos (vr_neto_cargo) - abonos,
 * acumulado en orden cronológico (fecha, número de planilla, registro).
 */
class CarteraSaldoService
{
    /**
     * Saldo acumulado de todos los movimientos anteriores a $fecha.
     */
    public function saldoAnterior(int $customerId, string $fecha): float
    {
        $totales = CarteraMovimiento::query()
            ->where('customer_id', $customerId)
            ->whereDate('fecha', '<', $fecha)
            ->selectRaw('COALESCE(SUM(vr_neto_cargo), 0) as cargos, COALESCE(SUM(abonos), 0) as abonos')
            ->first();

        return (float) $totales->cargos - (float) $totales->abonos;
    }

    /**
     * Estado de cuenta del cliente entre dos fechas: saldo anterior (saldo
     * final del día anterior a $desde, incluido el saldo inicial) + cargos -
     * abonos = saldo final. Cada movimiento trae en `saldo_corrido` el saldo
     * acumulado del estado de cuenta; puede ser negativo (anticipos).
     *
     * @return array{saldoAnterior: float, movimientos: Collection<int, CarteraMovimiento>, totalCargos: float, totalAbonos: float, saldoFinal: float}
     */
    public function estadoDeCuenta(int $customerId, string $desde, string $hasta): array
    {
        $saldoAnterior = $this->saldoAnterior($customerId, $desde);

        $movimientos = $this->ordenar(
            CarteraMovimiento::query()
                ->with('turno')
                ->where('customer_id', $customerId)
                ->whereDate('fecha', '>=', $desde)
                ->whereDate('fecha', '<=', $hasta)
                ->get()
        );

        $saldo = $saldoAnterior;

        foreach ($movimientos as $movimiento) {
            $saldo += (float) $movimiento->vr_neto_cargo - (float) $movimiento->abonos;
            $movimiento->saldo_corrido = $saldo;
        }

        return [
            'saldoAnterior' => $saldoAnterior,
            'movimientos' => $movimientos,
            'totalCargos' => (float) $movimientos->sum(fn (CarteraMovimiento $m): float => (float) $m->vr_neto_cargo),
            'totalAbonos' => (float) $movimientos->sum(fn (CarteraMovimiento $m): float => (float) $m->abonos),
            'saldoFinal' => $saldo,
        ];
    }

    /**
     * @param  Collection<int, CarteraMovimiento>  $movimientos
     * @return Collection<int, CarteraMovimiento>
     */
    public function ordenar(Collection $movimientos): Collection
    {
        return $movimientos->sortBy([
            fn (CarteraMovimiento $a, CarteraMovimiento $b): int => ($a->fecha?->timestamp ?? PHP_INT_MAX) <=> ($b->fecha?->timestamp ?? PHP_INT_MAX),
            fn (CarteraMovimiento $a, CarteraMovimiento $b): int => (int) $a->planillas <=> (int) $b->planillas,
            fn (CarteraMovimiento $a, CarteraMovimiento $b): int => $a->id <=> $b->id,
        ])->values();
    }

    /**
     * Recalcula y guarda la columna saldo (acumulada) de todos los movimientos del cliente.
     */
    public function recalcular(int $customerId): void
    {
        $saldo = 0.0;

        $movimientos = $this->ordenar(
            CarteraMovimiento::query()->where('customer_id', $customerId)->get()
        );

        foreach ($movimientos as $movimiento) {
            $saldo += (float) $movimiento->vr_neto_cargo - (float) $movimiento->abonos;

            if ((float) $movimiento->saldo !== $saldo) {
                $movimiento->update(['saldo' => $saldo]);
            }
        }
    }
}
