<?php

namespace App\Services;

use App\Models\Turno;

/**
 * Cuadre de una planilla de turno con la misma fórmula del resumen de la planilla:
 *
 * total recibido = consignaciones + pagos electrónicos + puntos redimidos + gasolina EDS
 *                  + descuentos + cartera + varios + sobrante + faltante trasladados
 *                  - recaudos y anticipos por islas
 * ventas IAPROPIADA = combustible según cierres de IAPROPIADA + canastilla (con IVA)
 *
 * Pendiente = recibido - ventas (positivo = sobrante, negativo = faltante). Se calcula en
 * el servidor para que la revisión no dependa del valor que envíe el navegador.
 */
class CuadreTurnoService
{
    public const RELACIONES_RECIBIDO = ['consignaciones', 'qrPagos', 'transferencias', 'gasolinaEds', 'descuentos', 'cartera', 'varios', 'recaudos'];

    public const RELACIONES_VENTAS = ['ventas', 'lubricantes'];

    public function totalRecibido(Turno $turno): float
    {
        $turno->loadMissing(self::RELACIONES_RECIBIDO);

        return (float) $turno->consignaciones->sum('valor')
            + (float) $turno->qrPagos->sum('valor')
            + (float) $turno->transferencias->sum('puntos_redimidos')
            + (float) $turno->gasolinaEds->sum('valor')
            + (float) $turno->descuentos->sum('valor')
            + (float) $turno->cartera->sum('valor')
            + (float) $turno->varios->sum('valor')
            + (float) $turno->traslado_sobrante
            + (float) $turno->traslado_faltante
            - (float) $turno->recaudos->sum('valor');
    }

    public function ventasIapropiada(Turno $turno): float
    {
        $turno->loadMissing(self::RELACIONES_VENTAS);

        // Cada venta (galones × precio) se redondea a pesos antes de sumar, como en la pantalla.
        // Sumar los centavos dejaba un sobrante/faltante de $1 que la planilla mostraba en 0 y
        // no se podía trasladar, pero impedía marcarla como revisada.
        return (float) $turno->ventas->sum(fn ($venta): float => round((float) $venta->valor))
            + (float) $turno->lubricantes->sum('total');
    }

    public function pendiente(Turno $turno): float
    {
        return round($this->totalRecibido($turno) - $this->ventasIapropiada($turno));
    }
}
