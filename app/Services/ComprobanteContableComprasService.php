<?php

namespace App\Services;

use App\Models\Compra;
use App\Models\CompraLubricante;
use App\Models\ComprobanteCompraCuenta;
use Illuminate\Support\Collection;

/**
 * Comprobante contable de compras del periodo, armado factura por factura desde el
 * módulo de compras. No se digita: cuentas, terceros y NIT salen de sus tablas.
 *
 * - Combustible (cada factura): débito inventario de combustibles / crédito proveedor
 *   de combustibles por el valor total de la factura (el combustible no lleva IVA).
 * - Canastilla (cada factura): débito inventario por el valor sin IVA, débito IVA si
 *   lo tiene y crédito proveedor por el total con IVA.
 *
 * La línea de descuentos compras (ingreso por mayorista) queda pendiente del módulo
 * de margen mayorista.
 */
class ComprobanteContableComprasService
{
    /**
     * @return array{
     *     lineas: list<array{fecha: string, cuenta: string, concepto: string, tercero: string, nit: string, debito: float, credito: float}>,
     *     totales: array{debito: float, credito: float},
     *     cuentas: Collection<string, ComprobanteCompraCuenta>
     * }
     */
    public function generar(string $desde, string $hasta): array
    {
        $cuentas = ComprobanteCompraCuenta::query()->with('proveedor')->get()->keyBy('clave');

        $lineas = $this->combustibles($cuentas, $desde, $hasta)
            ->concat($this->canastilla($cuentas, $desde, $hasta))
            ->values()
            ->all();

        return [
            'lineas' => $lineas,
            'totales' => [
                'debito' => (float) array_sum(array_column($lineas, 'debito')),
                'credito' => (float) array_sum(array_column($lineas, 'credito')),
            ],
            'cuentas' => $cuentas,
        ];
    }

    /**
     * @param  Collection<string, ComprobanteCompraCuenta>  $cuentas
     * @return Collection<int, array<string, mixed>>
     */
    private function combustibles(Collection $cuentas, string $desde, string $hasta): Collection
    {
        $proveedor = $cuentas->get(ComprobanteCompraCuenta::PROVEEDOR_COMBUSTIBLES)?->proveedor;

        return Compra::query()
            ->whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hasta)
            ->orderBy('fecha')
            ->orderBy('factura')
            ->get()
            ->filter(fn (Compra $compra): bool => (float) $compra->vr_total_fra > 0)
            ->flatMap(function (Compra $compra) use ($cuentas, $proveedor): array {
                $valor = round((float) $compra->vr_total_fra, 0);
                $base = [
                    'fecha' => $compra->fecha->format('Y-m-d'),
                    'concepto' => $this->concepto((string) $compra->factura),
                    'tercero' => (string) $proveedor?->name,
                    'nit' => (string) $proveedor?->document,
                ];

                return [
                    $this->linea($base, $cuentas, ComprobanteCompraCuenta::INVENTARIO_COMBUSTIBLES, $valor, 0.0),
                    $this->linea($base, $cuentas, ComprobanteCompraCuenta::PROVEEDOR_COMBUSTIBLES, 0.0, $valor),
                ];
            });
    }

    /**
     * Las compras de canastilla se registran por producto; el comprobante va por factura
     * (proveedor + fecha + No. FC), sumando sus productos.
     *
     * @param  Collection<string, ComprobanteCompraCuenta>  $cuentas
     * @return Collection<int, array<string, mixed>>
     */
    private function canastilla(Collection $cuentas, string $desde, string $hasta): Collection
    {
        return CompraLubricante::query()
            ->with('proveedor')
            ->whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hasta)
            ->orderBy('fecha')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (CompraLubricante $compra): string => $compra->fecha->format('Y-m-d')
                .'|'.$compra->proveedor_id
                .'|'.trim((string) $compra->no_fc))
            ->flatMap(function (Collection $factura) use ($cuentas): array {
                /** @var CompraLubricante $primera */
                $primera = $factura->first();
                $sinIva = round((float) $factura->sum('vr_sin_iva'), 0);
                $iva = round((float) $factura->sum('iva'), 0);
                $total = round((float) $factura->sum('total'), 0);
                $base = [
                    'fecha' => $primera->fecha->format('Y-m-d'),
                    'concepto' => $this->concepto((string) $primera->no_fc),
                    'tercero' => (string) $primera->proveedor?->name,
                    'nit' => (string) $primera->proveedor?->document,
                ];

                return array_values(array_filter([
                    $this->linea($base, $cuentas, ComprobanteCompraCuenta::INVENTARIO_CANASTILLA, $sinIva, 0.0),
                    $iva > 0 ? $this->linea($base, $cuentas, ComprobanteCompraCuenta::IVA_CANASTILLA, $iva, 0.0) : null,
                    $this->linea($base, $cuentas, ComprobanteCompraCuenta::PROVEEDOR_CANASTILLA, 0.0, $total),
                ]));
            });
    }

    /**
     * @param  array{fecha: string, concepto: string, tercero: string, nit: string}  $base
     * @param  Collection<string, ComprobanteCompraCuenta>  $cuentas
     * @return array{fecha: string, cuenta: string, concepto: string, tercero: string, nit: string, debito: float, credito: float}
     */
    private function linea(array $base, Collection $cuentas, string $clave, float $debito, float $credito): array
    {
        return [
            'fecha' => $base['fecha'],
            'cuenta' => (string) $cuentas->get($clave)?->cuenta,
            'concepto' => $base['concepto'],
            'tercero' => $base['tercero'],
            'nit' => $base['nit'],
            'debito' => $debito,
            'credito' => $credito,
        ];
    }

    private function concepto(string $factura): string
    {
        $factura = trim($factura);

        return 'FC '.($factura !== '' ? $factura : 's/n');
    }
}
