<?php

namespace App\Concerns;

use App\Models\TurnoQrPago;
use App\Support\FilaPlanilla;
use App\Support\NumberParser;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Validación de la planilla de turnos: reglas de cada campo de cada fila y las reglas de
 * negocio que la pantalla ya impide (respaldo del lado del servidor).
 */
trait ValidaPlanillaTurno
{
    /**
     * @return array<string, mixed>
     */
    protected function reglasPlanilla(): array
    {
        return [
            'fecha' => ['required', 'date'],
            'numero_turno' => ['required', 'integer', 'min:1'],
            'nombre_vendedor' => ['nullable', 'string', 'max:120'],

            'ventas' => ['nullable', 'array'],
            'lecturas' => ['nullable', 'array'],
            'urea_lubricantes' => ['nullable', 'array'],
            'consignaciones' => ['nullable', 'array'],
            'descuentos' => ['nullable', 'array'],
            'cartera' => ['nullable', 'array'],
            'qr_pagos' => ['nullable', 'array'],
            'recaudos' => ['nullable', 'array'],
            'transferencias' => ['nullable', 'array'],
            'gasolina_eds' => ['nullable', 'array'],
            'varios' => ['nullable', 'array'],
            'recaudos_admin' => ['nullable', 'array'],
            'traslado_sobrante' => ['nullable'],
            'traslado_faltante' => ['nullable'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'qr_pagos.*.concepto' => ['nullable', Rule::in(TurnoQrPago::CONCEPTOS)],

            // Cada fila se valida campo por campo: un dato fuera de rango no llega a la base de datos.
            'ventas.*.surtidor' => ['nullable', 'string', 'max:60'],
            'ventas.*.combustible' => ['nullable', Rule::in(['CTE', 'ACPM'])],
            'ventas.*.galones' => ['nullable', 'string', 'max:30'],
            'lecturas.*.manguera' => ['nullable', 'string', 'max:60'],
            'lecturas.*.combustible' => ['nullable', Rule::in(['corriente', 'acpm'])],
            'lecturas.*.lectura_inicial' => ['nullable', 'string', 'max:30'],
            'lecturas.*.lectura_final' => ['nullable', 'string', 'max:30'],
            'urea_lubricantes.*.producto' => ['nullable', 'string', 'max:150'],
            'urea_lubricantes.*.cantidad' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'urea_lubricantes.*.valor_sin_iva' => ['nullable', 'string', 'max:30'],
            'urea_lubricantes.*.iva' => ['nullable', 'string', 'max:30'],
            'urea_lubricantes.*.total' => ['nullable', 'string', 'max:30'],
            'consignaciones.*.consignacion_no' => ['nullable', 'string', 'max:60'],
            'consignaciones.*.consignacion_valor' => ['nullable', 'string', 'max:30'],
            'descuentos.*.descuento_no' => ['nullable', 'string', 'max:60'],
            'descuentos.*.descuento' => ['nullable', 'string', 'max:30'],
            'cartera.*.cartera_factura_no' => ['nullable', 'string', 'max:60'],
            'cartera.*.cliente_id' => ['nullable', 'integer', 'exists:customers,id'],
            'cartera.*.cartera_valor' => ['nullable', 'string', 'max:30'],
            'qr_pagos.*.valor' => ['nullable', 'string', 'max:30'],
            'recaudos.*.cliente_id' => ['nullable', 'integer', 'exists:customers,id'],
            'recaudos.*.valor' => ['nullable', 'string', 'max:30'],
            'transferencias.*.puntos' => ['nullable', 'string', 'max:30'],
            'gasolina_eds.*.puntos' => ['nullable', 'string', 'max:30'],
            'varios.*.concepto' => ['nullable', 'string', 'max:150'],
            'varios.*.valor' => ['nullable', 'string', 'max:30'],
            'recaudos_admin.*.banco' => ['nullable', 'string', 'max:60'],
            'recaudos_admin.*.responsable_id' => ['nullable', 'integer', 'exists:customers,id'],
            'recaudos_admin.*.valor' => ['nullable', 'string', 'max:30'],
        ];
    }

    /**
     * CARTERA - CRÉDITO DIRECTO, RECAUDOS Y ANTICIPOS POR ISLAS y RECAUDOS POR
     * ADMINISTRACIÓN no admiten un valor sin CLIENTE; en CRÉDITO DIRECTO el cliente y el
     * valor exigen el No. de FACTURA, y un pago electrónico con valor exige su CONCEPTO.
     */
    protected function validarReglasDeNegocioPlanilla(Request $request): void
    {
        $secciones = [
            'cartera' => ['valor' => 'cartera_valor', 'cliente' => 'cliente_id', 'titulo' => 'CARTERA - CRÉDITO DIRECTO'],
            'recaudos' => ['valor' => 'valor', 'cliente' => 'cliente_id', 'titulo' => 'RECAUDOS Y ANTICIPOS POR ISLAS'],
        ];

        // El islero no alimenta RECAUDOS POR ADMINISTRACIÓN (sus filas no se guardan).
        if (! $request->user()->isIslero()) {
            $secciones['recaudos_admin'] = ['valor' => 'valor', 'cliente' => 'responsable_id', 'titulo' => 'RECAUDOS POR ADMINISTRACIÓN'];
        }

        $errores = [];

        foreach ((array) $request->input('cartera', []) as $row) {
            if (! is_array($row) || trim((string) ($row['cartera_factura_no'] ?? '')) !== '') {
                continue;
            }

            if (! empty($row['cliente_id']) || NumberParser::money($row['cartera_valor'] ?? null) != 0) {
                $errores['cartera'] = 'En CARTERA - CRÉDITO DIRECTO hay filas sin No. de FACTURA. Digite la factura antes del cliente y el valor.';
            }
        }

        foreach ((array) $request->input('qr_pagos', []) as $row) {
            if (is_array($row) && ! FilaPlanilla::vacia($row, ['valor']) && trim((string) ($row['concepto'] ?? '')) === '') {
                $errores['qr_pagos'] = 'En PAGOS ELECTRÓNICOS Y TRANSFERENCIAS hay valores sin CONCEPTO. Seleccione el concepto de cada fila.';
            }
        }

        foreach ($secciones as $campo => $seccion) {
            if (isset($errores[$campo])) {
                continue;
            }

            foreach ((array) $request->input($campo, []) as $row) {
                if (! is_array($row)) {
                    continue;
                }

                if (NumberParser::money($row[$seccion['valor']] ?? null) != 0 && empty($row[$seccion['cliente']])) {
                    $errores[$campo] = 'En '.$seccion['titulo'].' hay valores sin CLIENTE. Seleccione el cliente de cada fila.';
                }
            }
        }

        if ($errores) {
            throw ValidationException::withMessages($errores);
        }
    }
}
