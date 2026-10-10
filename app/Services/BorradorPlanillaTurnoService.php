<?php

namespace App\Services;

use App\Models\FuelPrice;
use App\Models\Turno;
use App\Models\TurnoCartera;
use App\Models\TurnoConsignacion;
use App\Models\TurnoDescuento;
use App\Models\TurnoGasolinaEds;
use App\Models\TurnoLubricante;
use App\Models\TurnoQrPago;
use App\Models\TurnoRecaudo;
use App\Models\TurnoRecaudoAdmin;
use App\Models\TurnoSurtidor;
use App\Models\TurnoTransferencia;
use App\Models\TurnoVarios;
use App\Models\TurnoVenta;
use App\Models\User;
use App\Support\NumberParser;
use Illuminate\Support\Collection;

/**
 * Cuando el guardado de la planilla no pasa la validación, arma la planilla con lo que se
 * había digitado (sin guardar nada), con las mismas filas y atributos que usan las secciones
 * para mostrar una planilla guardada. Así no se pierde lo digitado.
 */
class BorradorPlanillaTurnoService
{
    /**
     * @param  array<string, mixed>  $enviado  Datos del intento de guardado (old input).
     */
    public function desdeEnvio(array $enviado, ?Turno $turno, User $usuario): Turno
    {
        $seccion = fn (string $nombre): array => array_values(array_filter(
            (array) ($enviado[$nombre] ?? []),
            'is_array',
        ));

        $numero = (int) ($enviado['numero_turno'] ?? 0);
        $fecha = $enviado['fecha'] ?? null;
        $borrador = $turno?->numero_turno === $numero
            ? $turno
            : (Turno::query()->where('numero_turno', $numero)->first() ?? new Turno([
                'numero_turno' => $numero,
                'precio_corriente' => (float) FuelPrice::activePriceOn('Gasolina', $fecha),
                'precio_acpm' => (float) FuelPrice::activePriceOn('ACPM', $fecha),
            ]));

        $borrador->fill([
            'fecha' => $fecha ?: now()->toDateString(),
            'observaciones' => $enviado['observaciones'] ?? null,
        ]);

        $filas = fn (string $clase, array $filas): Collection => collect($filas)->map(fn (array $atributos) => new $clase($atributos));

        $borrador->setRelation('ventas', $filas(TurnoVenta::class, array_map(fn (array $r): array => [
            'surtidor' => $r['surtidor'] ?? '', 'combustible' => $r['combustible'] ?? null,
            'galones' => NumberParser::quantity($r['galones'] ?? null), 'valor' => 0,
        ], $seccion('ventas'))));
        $borrador->setRelation('surtidores', $filas(TurnoSurtidor::class, array_map(fn (array $r): array => [
            'manguera' => $r['manguera'] ?? '', 'combustible' => $r['combustible'] ?? null,
            'lectura_inicial' => NumberParser::quantity($r['lectura_inicial'] ?? null),
            'lectura_final' => NumberParser::quantity($r['lectura_final'] ?? null),
            'galones' => max(0, NumberParser::quantity($r['lectura_final'] ?? null) - NumberParser::quantity($r['lectura_inicial'] ?? null)),
        ], $seccion('lecturas'))));
        $borrador->setRelation('lubricantes', $filas(TurnoLubricante::class, array_map(fn (array $r): array => [
            'cantidad' => (int) ($r['cantidad'] ?? 0), 'producto' => $r['producto'] ?? null,
            'valor_sin_iva' => NumberParser::money($r['valor_sin_iva'] ?? null), 'iva' => NumberParser::money($r['iva'] ?? null),
            'total' => NumberParser::money($r['total'] ?? null),
        ], $seccion('urea_lubricantes'))));
        $borrador->setRelation('consignaciones', $filas(TurnoConsignacion::class, array_map(fn (array $r): array => [
            'consignacion_no' => $r['consignacion_no'] ?? null, 'valor' => NumberParser::money($r['consignacion_valor'] ?? null),
        ], $seccion('consignaciones'))));
        $borrador->setRelation('descuentos', $filas(TurnoDescuento::class, array_map(fn (array $r): array => [
            'descuento_no' => $r['descuento_no'] ?? null, 'valor' => NumberParser::money($r['descuento'] ?? null),
        ], $seccion('descuentos'))));
        $borrador->setRelation('cartera', $filas(TurnoCartera::class, array_map(fn (array $r): array => [
            'factura_no' => $r['cartera_factura_no'] ?? null, 'cliente_id' => $r['cliente_id'] ?? null,
            'valor' => NumberParser::money($r['cartera_valor'] ?? null),
        ], $seccion('cartera'))));
        $borrador->setRelation('qrPagos', $filas(TurnoQrPago::class, array_map(fn (array $r): array => [
            'concepto' => $r['concepto'] ?? null, 'valor' => NumberParser::money($r['valor'] ?? null),
        ], $seccion('qr_pagos'))));
        $borrador->setRelation('recaudos', $filas(TurnoRecaudo::class, array_map(fn (array $r): array => [
            'cliente_id' => $r['cliente_id'] ?? null, 'valor' => NumberParser::money($r['valor'] ?? null),
        ], $seccion('recaudos'))));
        $borrador->setRelation('transferencias', $filas(TurnoTransferencia::class, array_map(fn (array $r): array => [
            'puntos_redimidos' => NumberParser::money($r['puntos'] ?? null),
        ], $seccion('transferencias'))));
        $borrador->setRelation('gasolinaEds', $filas(TurnoGasolinaEds::class, array_map(fn (array $r): array => [
            'valor' => NumberParser::money($r['puntos'] ?? null),
        ], $seccion('gasolina_eds'))));
        $borrador->setRelation('varios', $filas(TurnoVarios::class, array_map(fn (array $r): array => [
            'concepto' => $r['concepto'] ?? null, 'valor' => NumberParser::money($r['valor'] ?? null),
        ], $seccion('varios'))));

        if (! $usuario->isIslero()) {
            $borrador->setRelation('recaudosAdmin', $filas(TurnoRecaudoAdmin::class, array_map(fn (array $r): array => [
                'banco' => $r['banco'] ?? null, 'responsable_id' => $r['responsable_id'] ?? null,
                'valor' => NumberParser::money($r['valor'] ?? null),
            ], $seccion('recaudos_admin'))));
        }

        return $borrador;
    }
}
