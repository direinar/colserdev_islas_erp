<?php

namespace App\Services;

use App\Models\ComprobanteVentaCuenta;
use App\Models\Turno;
use App\Models\TurnoCartera;
use App\Models\TurnoVarios;
use Illuminate\Support\Collection;

/**
 * Informe Control Caja: una fila por planilla de turno con sus ventas, formas de pago y
 * resultados; y el comprobante contable de ventas del periodo (módulo aparte). Todo sale de la planilla de
 * turnos; el "total recibido" se recalcula aquí con la misma fórmula del resumen de la
 * planilla, que solo lo calcula en pantalla y no lo guarda.
 *
 * Signos como en la planilla: el faltante trasladado es positivo, el sobrante negativo y
 * los recaudos y anticipos de islas restan.
 */
class ControlCajaService
{
    /**
     * Columnas que se totalizan al pie del informe.
     */
    public const COLUMNAS_TOTALES = [
        'gasolina', 'acpm', 'canastilla', 'iva', 'total_ventas',
        'consignaciones', 'pagos_electronicos', 'puntos', 'gasolina_eds', 'descuentos', 'cartera',
        'faltantes', 'sobrantes', 'recaudos', 'total', 'recibido_planilla', 'recibido_lectura',
    ];

    /**
     * La plantilla tiene cuatro pares concepto/valor de VARIOS.
     */
    public const CASILLAS_VARIOS = 4;

    public function __construct(
        private KardexInventarioService $kardex,
        private CuadreTurnoService $cuadre,
    ) {}

    /**
     * Informe Control Caja: una fila por planilla y los totales del periodo.
     *
     * @return array{filas: list<array<string, mixed>>, totales: array<string, mixed>}
     */
    public function informe(string $desde, string $hasta): array
    {
        [$filas, $totales] = $this->filasYTotales($this->turnos($desde, $hasta));

        return ['filas' => $filas, 'totales' => $totales];
    }

    /**
     * Comprobante contable de ventas del periodo (módulo aparte) con las cuentas configuradas.
     *
     * @return array{comprobante: array<string, mixed>, cuentas: Collection<string, ComprobanteVentaCuenta>}
     */
    public function comprobanteVentas(string $desde, string $hasta): array
    {
        $turnos = $this->turnos($desde, $hasta);
        [, $totales] = $this->filasYTotales($turnos);
        $cuentas = ComprobanteVentaCuenta::query()->get()->keyBy('clave');

        return [
            'comprobante' => $this->comprobante($turnos, $totales, $cuentas, $desde, $hasta),
            'cuentas' => $cuentas,
        ];
    }

    /**
     * @return Collection<int, Turno>
     */
    private function turnos(string $desde, string $hasta): Collection
    {
        return Turno::query()
            ->whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hasta)
            ->with(['lubricantes', 'consignaciones', 'qrPagos', 'transferencias', 'gasolinaEds', 'descuentos', 'cartera.cliente', 'varios', 'recaudos', 'user'])
            ->orderBy('fecha')
            ->orderBy('numero_turno')
            ->get();
    }

    /**
     * @param  Collection<int, Turno>  $turnos
     * @return array{0: list<array<string, mixed>>, 1: array<string, mixed>}
     */
    private function filasYTotales(Collection $turnos): array
    {
        $filas = $turnos->map(fn (Turno $turno): array => $this->fila($turno))->all();

        $totales = collect(self::COLUMNAS_TOTALES)
            ->mapWithKeys(fn (string $columna): array => [$columna => (float) array_sum(array_column($filas, $columna))])
            ->all();
        $totales['varios'] = collect(range(0, self::CASILLAS_VARIOS - 1))
            ->map(fn (int $casilla): float => (float) collect($filas)->sum(fn (array $fila): float => $fila['varios'][$casilla]['valor']))
            ->all();

        return [$filas, $totales];
    }

    /**
     * @return array<string, mixed>
     */
    private function fila(Turno $turno): array
    {
        $gasolina = round((float) $turno->tirillas_valor_corriente, 0);
        $acpm = round((float) $turno->tirillas_valor_acpm, 0);
        $canastilla = round((float) $turno->lubricantes->sum('valor_sin_iva'), 0);
        $iva = round((float) $turno->lubricantes->sum('iva'), 0);

        $consignaciones = (float) $turno->consignaciones->sum('valor');
        $pagosElectronicos = (float) $turno->qrPagos->sum('valor');
        $puntos = (float) $turno->transferencias->sum('puntos_redimidos');
        $gasolinaEds = (float) $turno->gasolinaEds->sum('valor');
        $descuentos = (float) $turno->descuentos->sum('valor');
        $cartera = (float) $turno->cartera->sum('valor');
        $varios = $this->casillasVarios($turno->varios);
        $faltantes = (float) $turno->traslado_faltante;
        $sobrantes = (float) $turno->traslado_sobrante;
        $recaudos = -(float) $turno->recaudos->sum('valor');

        // Misma fórmula del resumen de la planilla y del cuadre que bloquea la revisión.
        $total = $this->cuadre->totalRecibido($turno);

        return [
            'turno_id' => $turno->id,
            'fecha' => $turno->fecha->format('Y-m-d'),
            'numero_turno' => $turno->numero_turno,
            'gasolina' => $gasolina,
            'acpm' => $acpm,
            'canastilla' => $canastilla,
            'iva' => $iva,
            'total_ventas' => $gasolina + $acpm + $canastilla + $iva,
            'consignaciones' => $consignaciones,
            'pagos_electronicos' => $pagosElectronicos,
            'puntos' => $puntos,
            'gasolina_eds' => $gasolinaEds,
            'descuentos' => $descuentos,
            'cartera' => $cartera,
            'varios' => $varios,
            'faltantes' => $faltantes,
            'sobrantes' => $sobrantes,
            // Quien registró la planilla (se conserva aunque otro la corrija).
            'islero' => (string) ($turno->nombre_vendedor ?: $turno->user?->name),
            'recaudos' => $recaudos,
            'total' => $total,
            // Total recibido en el turno (resumen de la planilla) y venta según lecturas de surtidores.
            'recibido_planilla' => $total,
            'recibido_lectura' => round((float) $turno->total_venta_lecturas + (float) $turno->lubricantes->sum('total'), 0),
        ];
    }

    /**
     * Cuatro casillas concepto/valor; si un turno trae más, la cuarta acumula el resto.
     *
     * @param  Collection<int, TurnoVarios>  $varios
     * @return list<array{concepto: string, valor: float}>
     */
    private function casillasVarios(Collection $varios): array
    {
        $ordenados = $varios->sortBy('id')->values();
        $casillas = [];

        foreach (range(0, self::CASILLAS_VARIOS - 1) as $casilla) {
            $grupo = $casilla < self::CASILLAS_VARIOS - 1
                ? $ordenados->slice($casilla, 1)
                : $ordenados->slice($casilla);

            $casillas[] = [
                'concepto' => $grupo->pluck('concepto')->filter()->implode(' / '),
                'valor' => (float) $grupo->sum('valor'),
            ];
        }

        return $casillas;
    }

    /**
     * Comprobante contable de ventas del periodo.
     *
     * @param  Collection<int, Turno>  $turnos
     * @param  array<string, mixed>  $totales
     * @param  Collection<string, ComprobanteVentaCuenta>  $cuentas
     * @return array{lineas: list<array{cuenta: string, concepto: string, tercero: string, nit: string, debito: float, credito: float}>, debito: float, credito: float, diferencia: float}
     */
    private function comprobante(Collection $turnos, array $totales, Collection $cuentas, string $desde, string $hasta): array
    {
        $cuenta = fn (string $clave): string => (string) $cuentas->get($clave)?->cuenta;
        $linea = fn (string $clave, string $concepto, float $debito, float $credito, string $tercero = '', string $nit = ''): array => [
            'cuenta' => $cuenta($clave),
            'concepto' => $concepto,
            'tercero' => $tercero,
            'nit' => $nit,
            'debito' => round($debito, 0),
            'credito' => round($credito, 0),
        ];

        $faltantesSobrantes = $totales['faltantes'] + $totales['sobrantes'];
        $costoCombustible = $this->costoDeVentas(
            array_merge($this->kardex->gasolina()['rows'], $this->kardex->acpm()['rows']),
            $desde,
            $hasta,
        );
        $costoCanastilla = $this->costoDeVentas($this->kardex->canastillaControl()['rows'], $desde, $hasta);

        $lineas = [
            // Se restan los recaudos y anticipos por islas ($totales['recaudos'] ya es negativo):
            // su asiento (débito a caja, crédito a la cartera del cliente) está en el
            // comprobante de Recaudos de cartera y no se repite aquí.
            $linea('consignaciones_pagos', 'CONSIGNACIONES, TC, QR Y TRANSF.', $totales['consignaciones'] + $totales['pagos_electronicos'] + $totales['recaudos'], 0),
            $linea('descuentos_ventas', 'DESCUENTOS EN VENTAS', $totales['puntos'] + $totales['descuentos'], 0),
            $linea('gastos_gasolina_eds', 'GASTOS DE GASOLINA EDS', $totales['gasolina_eds'], 0),
            $linea('varios', 'VARIOS', array_sum($totales['varios']), 0),
            // Neto de faltantes y sobrantes: si predomina el sobrante, va al crédito.
            $linea('faltantes_sobrantes', 'FALT/SOBRANT/DIF. MENORES', max($faltantesSobrantes, 0), max(-$faltantesSobrantes, 0)),
            ...$this->carteraPorCliente($turnos, $linea),
            $linea('ventas_combustible', 'VENTAS COMBUSTIBLE', 0, $totales['gasolina'] + $totales['acpm']),
            $linea('costo_ventas_combustible', 'COSTO DE VENTAS DE COMBUSTIBLE', $costoCombustible, 0),
            $linea('inventario_combustibles', 'INVENTARIOS DE COMBUSTIBLES', 0, $costoCombustible),
            $linea('ventas_canastilla', 'VENTAS DE CANASTILLA', 0, $totales['canastilla']),
            $linea('costo_ventas_canastilla', 'COSTO DE VENTAS CANASTILLA', $costoCanastilla, 0),
            $linea('inventario_canastilla', 'INVENTARIOS DE CANASTILLA', 0, $costoCanastilla),
            $linea('iva_por_pagar', 'IVA POR PAGAR', 0, $totales['iva']),
        ];

        $debito = (float) array_sum(array_column($lineas, 'debito'));
        $credito = (float) array_sum(array_column($lineas, 'credito'));

        return ['lineas' => $lineas, 'debito' => $debito, 'credito' => $credito, 'diferencia' => $debito - $credito];
    }

    /**
     * Una línea por cliente (con su nombre y NIT como tercero) con sus ventas a crédito al
     * débito. Los recaudos y anticipos por islas no van aquí: los acredita a la cartera el
     * comprobante de Recaudos de cartera.
     *
     * @param  Collection<int, Turno>  $turnos
     * @return list<array{cuenta: string, concepto: string, tercero: string, nit: string, debito: float, credito: float}>
     */
    private function carteraPorCliente(Collection $turnos, callable $linea): array
    {
        $clientes = [];

        foreach ($turnos as $turno) {
            foreach ($turno->cartera as $venta) {
                /** @var TurnoCartera $venta */
                $clave = (string) ($venta->cliente_id ?? '');
                $clientes[$clave] ??= [
                    'tercero' => $venta->cliente?->name ?? 'SIN CLIENTE',
                    'nit' => (string) ($venta->cliente?->document ?? ''),
                    'debito' => 0.0,
                ];
                $clientes[$clave]['debito'] += (float) $venta->valor;
            }
        }

        uasort($clientes, fn (array $a, array $b): int => strcmp($a['tercero'], $b['tercero']));

        return array_values(array_map(
            fn (array $cliente): array => $linea('cartera_clientes', 'CARTERA CLIENTES', $cliente['debito'], 0, $cliente['tercero'], $cliente['nit']),
            $clientes,
        ));
    }

    /**
     * Suma de las salidas al costo promedio de las ventas del periodo.
     *
     * @param  list<array<string, mixed>>  $filas
     */
    private function costoDeVentas(array $filas, string $desde, string $hasta): float
    {
        return (float) collect($filas)
            ->filter(fn (array $fila): bool => $fila['tipo'] === 'venta'
                && $fila['fecha_venta'] >= $desde
                && $fila['fecha_venta'] <= $hasta)
            ->sum('valor_salidas');
    }
}
