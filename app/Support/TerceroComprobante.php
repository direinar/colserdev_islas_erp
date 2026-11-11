<?php

namespace App\Support;

/**
 * Regla de los comprobantes contables (ventas y compras): una línea sin NIT va con el
 * tercero GENERAL y el NIT en blanco, aunque traiga un nombre (un cliente o proveedor sin
 * documento registrado, "SIN CLIENTE", o las líneas de ventas, costos, inventarios e IVA).
 */
class TerceroComprobante
{
    public const GENERAL = 'GENERAL';

    /**
     * @template T of array{tercero: string, nit: string}
     *
     * @param  T  $linea
     * @return T
     */
    public static function aplicar(array $linea): array
    {
        if (trim((string) $linea['nit']) === '') {
            $linea['tercero'] = self::GENERAL;
            $linea['nit'] = '';
        }

        return $linea;
    }
}
