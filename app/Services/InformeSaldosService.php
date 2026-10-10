<?php

namespace App\Services;

use App\Models\CarteraMovimiento;
use App\Models\Customer;
use App\Models\Lubricant;

/**
 * Informes de saldos a una fecha de corte: saldo de cartera de cada cliente, existencias
 * de cada referencia de canastilla y precios de venta vigentes de canastilla.
 */
class InformeSaldosService
{
    public function __construct(private KardexInventarioService $kardex) {}

    /**
     * Saldo de cada cliente al cierre de la fecha de corte: cargos - abonos de todos sus
     * movimientos hasta ese día (incluido el saldo inicial). Negativo = anticipo.
     *
     * @return array{clientes: list<array{id: int, cliente: string, documento: string, saldo: float}>, por_cobrar: float, anticipos: float, neto: float, con_saldo: int}
     */
    public function cartera(string $corte, bool $soloConSaldo): array
    {
        $saldos = CarteraMovimiento::query()
            ->whereDate('fecha', '<=', $corte)
            ->selectRaw('customer_id, COALESCE(SUM(vr_neto_cargo), 0) - COALESCE(SUM(abonos), 0) as saldo')
            ->groupBy('customer_id')
            ->pluck('saldo', 'customer_id');

        $clientes = Customer::query()
            ->orderBy('name')
            ->get(['id', 'name', 'document'])
            ->map(fn (Customer $cliente): array => [
                'id' => $cliente->id,
                'cliente' => (string) $cliente->name,
                'documento' => (string) $cliente->document,
                'saldo' => round((float) ($saldos[$cliente->id] ?? 0), 0),
            ]);

        $conSaldo = $clientes->filter(fn (array $cliente): bool => $cliente['saldo'] != 0.0);

        return [
            'clientes' => ($soloConSaldo ? $conSaldo : $clientes)->values()->all(),
            'por_cobrar' => (float) $conSaldo->where('saldo', '>', 0)->sum('saldo'),
            'anticipos' => (float) $conSaldo->where('saldo', '<', 0)->sum('saldo'),
            'neto' => (float) $conSaldo->sum('saldo'),
            'con_saldo' => $conSaldo->count(),
        ];
    }

    /**
     * Existencias de cada referencia de canastilla al cierre de la fecha de corte, tomadas
     * de su inventario (kardex al costo promedio).
     *
     * @return array{referencias: list<array{referencia: string, activa: bool, unidades: float, promedio: float, valor: float}>, unidades: float, valor: float}
     */
    public function canastilla(string $corte): array
    {
        $catalogo = Lubricant::query()->pluck('active', 'reference');
        $referencias = $catalogo->keys()
            ->concat($this->kardex->referenciasCanastilla())
            ->filter()
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $filas = $referencias->map(function (string $referencia) use ($corte, $catalogo): array {
            $ultimo = collect($this->kardex->canastilla($referencia)['rows'])
                ->filter(fn (array $fila): bool => ($fila['fecha_compra'] ?? $fila['fecha_venta']) <= $corte)
                ->last();

            return [
                'referencia' => $referencia,
                'activa' => (bool) ($catalogo[$referencia] ?? false),
                'unidades' => (float) ($ultimo['saldo_unidades'] ?? 0),
                'promedio' => (float) ($ultimo['promedio'] ?? 0),
                'valor' => round((float) ($ultimo['valor_saldo'] ?? 0), 0),
            ];
        });

        return [
            'referencias' => $filas->all(),
            'unidades' => (float) $filas->sum('unidades'),
            'valor' => (float) $filas->sum('valor'),
        ];
    }

    /**
     * Precios de venta vigentes de las referencias activas de la tabla de precios de canastilla.
     *
     * @return list<array{referencia: string, precio: float, iva: float, total: float}>
     */
    public function precios(): array
    {
        return Lubricant::query()
            ->where('active', true)
            ->orderBy('reference')
            ->get()
            ->map(fn (Lubricant $producto): array => [
                'referencia' => (string) $producto->reference,
                'precio' => (float) $producto->sale_price,
                'iva' => (float) $producto->iva,
                'total' => (float) $producto->total,
            ])
            ->sortBy('referencia', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }
}
