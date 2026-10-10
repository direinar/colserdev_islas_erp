<?php

namespace App\Services;

use App\Models\GalonajeContrato;
use App\Models\Turno;

/**
 * Informe de cierre del periodo: estado de resultados de la EDS. No se digita nada salvo
 * el saldo inicial del contrato de galonaje; todo sale de los inventarios (kardex), la
 * planilla de turnos y el informe de margen mayorista.
 */
class InformeCierreService
{
    public function __construct(
        private KardexInventarioService $kardex,
        private MargenMayoristaService $margenMayorista,
    ) {}

    /**
     * @return array{valores: array<string, float>, estadisticas: array<string, float|null>, contrato: GalonajeContrato|null}
     */
    public function informe(string $desde, string $hasta): array
    {
        $acpm = $this->ventasDelPeriodo($this->kardex->acpm()['rows'], $desde, $hasta);
        $gasolina = $this->ventasDelPeriodo($this->kardex->gasolina()['rows'], $desde, $hasta);
        $canastilla = $this->ventasDelPeriodo($this->kardex->canastillaControl()['rows'], $desde, $hasta);

        $turnos = Turno::query()
            ->whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hasta)
            ->with(['transferencias', 'descuentos'])
            ->get();

        $totalVentas = $acpm['venta'] + $gasolina['venta'];
        $costoCombustible = round($acpm['costo'] + $gasolina['costo'], 0);
        $subtotal = $totalVentas - $costoCombustible;
        // Descuentos en ventas del comprobante contable: puntos redimidos + descuentos de las planillas.
        $descuentos = (float) $turnos->sum(fn (Turno $turno): float => (float) $turno->transferencias->sum('puntos_redimidos') + (float) $turno->descuentos->sum('valor'));
        $utilidadCombustibles = $subtotal - $descuentos;
        $margenMayorista = $this->margenMayorista->informe($desde, $hasta)['margen_mayorista'];
        $ingresosCombustibles = $utilidadCombustibles + $margenMayorista;
        $costoCanastilla = round($canastilla['costo'], 0);
        $utilidadCanastilla = $canastilla['venta'] - $costoCanastilla;

        $galonajePeriodo = (float) $turnos->sum(fn (Turno $turno): float => (float) $turno->tirillas_galones_corriente + (float) $turno->tirillas_galones_acpm);
        $contrato = GalonajeContrato::query()->latest('fecha_corte')->latest('id')->first();
        $vendidosDesdeCorte = $contrato !== null ? $this->galonesVendidos($contrato->fecha_corte->format('Y-m-d'), $hasta) : null;

        return [
            'valores' => [
                'ventas_acpm' => $acpm['venta'],
                'ventas_gasolina' => $gasolina['venta'],
                'total_ventas' => $totalVentas,
                'costo_combustible' => $costoCombustible,
                'subtotal' => $subtotal,
                'descuentos' => $descuentos,
                'utilidad_combustibles' => $utilidadCombustibles,
                'margen_mayorista' => $margenMayorista,
                'ingresos_combustibles' => $ingresosCombustibles,
                'venta_canastilla' => $canastilla['venta'],
                'costo_canastilla' => $costoCanastilla,
                'utilidad_canastilla' => $utilidadCanastilla,
                'utilidad_total' => $ingresosCombustibles + $utilidadCanastilla,
            ],
            'estadisticas' => [
                'margen_intermediacion' => $totalVentas > 0 ? $utilidadCombustibles / $totalVentas : null,
                'galonaje_periodo' => $galonajePeriodo,
                'galonaje_acumulado' => $contrato !== null ? (float) $contrato->galonaje_acumulado_inicial + $vendidosDesdeCorte : null,
                'saldo_contrato' => $contrato !== null ? (float) $contrato->saldo_contrato_inicial - $vendidosDesdeCorte : null,
            ],
            'contrato' => $contrato,
        ];
    }

    /**
     * Venta y costo (salidas al costo promedio) de las ventas del periodo en un kardex.
     *
     * @param  list<array<string, mixed>>  $filas
     * @return array{venta: float, costo: float}
     */
    private function ventasDelPeriodo(array $filas, string $desde, string $hasta): array
    {
        $ventas = collect($filas)->filter(fn (array $fila): bool => $fila['tipo'] === 'venta'
            && $fila['fecha_venta'] >= $desde
            && $fila['fecha_venta'] <= $hasta);

        return [
            'venta' => round((float) $ventas->sum('valor_venta'), 0),
            'costo' => (float) $ventas->sum('valor_salidas'),
        ];
    }

    /**
     * Galones vendidos (gasolina + ACPM según cortes de IAPROPIADA) desde la fecha de corte.
     */
    private function galonesVendidos(string $desde, string $hasta): float
    {
        if ($hasta < $desde) {
            return 0.0;
        }

        return (float) Turno::query()
            ->whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hasta)
            ->get(['tirillas_galones_corriente', 'tirillas_galones_acpm'])
            ->sum(fn (Turno $turno): float => (float) $turno->tirillas_galones_corriente + (float) $turno->tirillas_galones_acpm);
    }
}
