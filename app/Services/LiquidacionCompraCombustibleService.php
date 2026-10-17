<?php

namespace App\Services;

use App\Models\Compra;
use App\Support\NumberParser;

/**
 * Liquidación de una factura de compra de combustible, igual que la plantilla
 * "MODULO COMPRAS ADICIONADO IVA". Por cada combustible:
 *
 * (1) Galones, base del 19%, base del 5%, costo producto, sobretasa y Soldicom: los digita el administrador.
 * (2) Liquidación IVA = base 19% x 19% + base 5% x 5%.
 * (3) Costo = liquidación IVA + costo producto + sobretasa + Soldicom (antes "distrib. costo").
 * (4) Costo unitario = costo / galones (no se guarda: Compra::costoUnitario()).
 *
 * Total valor = costo gasolina + costo ACPM; total galones = galones gasolina + galones ACPM.
 */
class LiquidacionCompraCombustibleService
{
    /**
     * Campos que se digitan por combustible.
     */
    public const CAMPOS_DIGITADOS = ['base19', 'base5', 'costo_producto', 'sobretasa', 'soldicom'];

    /**
     * @param  array<string, mixed>  $fila  Lo enviado en el formulario (montos en formato es-CO).
     * @return array<string, float> Columnas de la compra con las fórmulas resueltas.
     */
    public function liquidar(array $fila): array
    {
        $datos = [];

        foreach (Compra::COMBUSTIBLES as $combustible) {
            $galones = NumberParser::decimal($fila[$combustible] ?? null);
            $valores = [];
            foreach (self::CAMPOS_DIGITADOS as $campo) {
                $valores[$campo] = NumberParser::decimal($fila[$campo.'_'.$combustible] ?? null);
            }

            $iva = round($valores['base19'] * Compra::TARIFA_IVA_GENERAL + $valores['base5'] * Compra::TARIFA_IVA_REDUCIDA, 2);

            $datos[$combustible] = $galones;
            foreach ($valores as $campo => $valor) {
                $datos[$campo.'_'.$combustible] = $valor;
            }
            $datos['iva_'.$combustible] = $iva;
            $datos['distribucion_'.$combustible] = round($iva + $valores['costo_producto'] + $valores['sobretasa'] + $valores['soldicom'], 2);
        }

        $datos['vr_total_fra'] = $datos['distribucion_gasolina'] + $datos['distribucion_acpm'];
        $datos['total'] = $datos['gasolina'] + $datos['acpm'];

        return $datos;
    }

    /**
     * Si la fila no trae ningún galón ni valor (las filas en blanco no se guardan).
     *
     * @param  array<string, float>  $liquidacion
     */
    public function vacia(array $liquidacion): bool
    {
        return collect($liquidacion)->every(fn (float $valor): bool => $valor == 0.0);
    }
}
