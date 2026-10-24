<?php

namespace App\Services;

use App\Models\FuelPrice;
use App\Models\Turno;
use App\Models\User;
use App\Support\FilaPlanilla;
use App\Support\NumberParser;
use Illuminate\Support\Facades\DB;

/**
 * Guarda la planilla de turnos completa en una transacción: encabezado, las doce secciones,
 * los totales que leen los informes, el sobrante/faltante pendiente y la cartera.
 *
 * Recibe los datos ya validados (ValidaPlanillaTurno) y autorizados por el controlador.
 * Una planilla existente se reemplaza sección por sección: se borran sus filas y se crean
 * las enviadas, salvo RECAUDOS POR ADMINISTRACIÓN, que el islero no puede tocar.
 */
class GuardarPlanillaTurnoService
{
    public function __construct(
        private CarteraTurnoSyncService $carteraSync,
        private CuadreTurnoService $cuadre,
    ) {}

    /**
     * @param  array<string, mixed>  $datos
     */
    public function guardar(array $datos, User $usuario): Turno
    {
        return DB::transaction(function () use ($datos, $usuario): Turno {
            // numero_turno es un consecutivo único en toda la tabla: si existe, se actualiza.
            $turno = Turno::query()
                ->where('numero_turno', $datos['numero_turno'])
                ->lockForUpdate()
                ->first();

            [$trasladoSobrante, $trasladoFaltante] = $this->traslado($datos, $turno, $usuario);

            $atributos = [
                'fecha' => $datos['fecha'],
                'numero_turno' => $datos['numero_turno'],
                // El vendedor es quien registró la planilla; si otro la corrige, no se reemplaza.
                'nombre_vendedor' => $turno?->nombre_vendedor ?: $usuario->name,
                'observaciones' => $datos['observaciones'] ?? null,
                // El precio rige desde un número de turno en Precios de Combustible (no
                // desde una fecha). Sin fallback a config(): si no hay registro, queda en 0.
                'precio_corriente' => (float) FuelPrice::precioParaTurno('Gasolina', (int) $datos['numero_turno']),
                'precio_acpm' => (float) FuelPrice::precioParaTurno('ACPM', (int) $datos['numero_turno']),
                // Estado del traslado de sobrante/faltante (botón TRASLADAR en
                // sobrantes.blade.php): debe persistir para que el resumen no
                // vuelva a mostrar el faltante/sobrante original al recargar.
                'traslado_sobrante' => $trasladoSobrante,
                'traslado_faltante' => $trasladoFaltante,
            ];

            if ($turno) {
                $turno->update($atributos);
                $this->borrarFilas($turno, $usuario);
            } else {
                $turno = Turno::create($atributos + ['user_id' => $usuario->id]);
            }

            $this->saveVentas($turno, $this->seccion($datos, 'ventas'));
            $this->saveLecturas($turno, $this->seccion($datos, 'lecturas'));
            $this->saveUreaLubricantes($turno, $this->seccion($datos, 'urea_lubricantes'));
            $this->saveConsignaciones($turno, $this->seccion($datos, 'consignaciones'));
            $this->saveDescuentos($turno, $this->seccion($datos, 'descuentos'));
            $this->saveCartera($turno, $this->seccion($datos, 'cartera'));
            $this->saveQrPagos($turno, $this->seccion($datos, 'qr_pagos'));
            $this->saveRecaudos($turno, $this->seccion($datos, 'recaudos'));
            $this->saveTransferencias($turno, $this->seccion($datos, 'transferencias'));
            $this->saveGasolinaEds($turno, $this->seccion($datos, 'gasolina_eds'));
            $this->saveVarios($turno, $this->seccion($datos, 'varios'));
            if (! $usuario->isIslero()) {
                $this->saveRecaudosAdmin($turno, $this->seccion($datos, 'recaudos_admin'));
            }
            $this->saveVentasTotales($turno);
            $this->saveVentaLecturasTotales($turno);

            // Sobrante/faltante sin trasladar, calculado aquí y no en el navegador:
            // impide marcar la planilla como revisada mientras no sea cero.
            $turno->update(['sobrante_pendiente' => $this->cuadre->pendiente($turno->fresh())]);

            // Al final: la cartera lee créditos y recaudos ya guardados del turno.
            $this->carteraSync->sincronizar($turno);

            return $turno;
        });
    }

    /**
     * Filas de una sección del formulario (solo las que son arreglos).
     *
     * @param  array<string, mixed>  $datos
     * @return list<array<string, mixed>>
     */
    private function seccion(array $datos, string $nombre): array
    {
        return array_values(array_filter((array) ($datos[$nombre] ?? []), 'is_array'));
    }

    /**
     * Traslado de sobrante/faltante a guardar. Reversar = enviarlo en cero cuando ya había uno
     * guardado, y eso solo lo hace el administrador; ajustarlo (trasladar una diferencia nueva)
     * sí lo puede hacer quien modifica la planilla.
     *
     * @param  array<string, mixed>  $datos
     * @return array{0: float, 1: float}
     */
    private function traslado(array $datos, ?Turno $turno, User $usuario): array
    {
        $sobrante = $this->trasladoValue($datos['traslado_sobrante'] ?? null);
        $faltante = $this->trasladoValue($datos['traslado_faltante'] ?? null);
        $reversa = $sobrante === 0.0 && $faltante === 0.0;

        if ($turno && $reversa && ! $usuario->isAdministrador()
            && ((float) $turno->traslado_sobrante !== 0.0 || (float) $turno->traslado_faltante !== 0.0)) {
            return [(float) $turno->traslado_sobrante, (float) $turno->traslado_faltante];
        }

        return [$sobrante, $faltante];
    }

    private function borrarFilas(Turno $turno, User $usuario): void
    {
        $turno->ventas()->delete();
        $turno->surtidores()->delete();
        $turno->lubricantes()->delete();
        $turno->consignaciones()->delete();
        $turno->descuentos()->delete();
        $turno->cartera()->delete();
        $turno->qrPagos()->delete();
        $turno->recaudos()->delete();
        $turno->transferencias()->delete();
        $turno->gasolinaEds()->delete();
        $turno->varios()->delete();
        // RECAUDOS POR ADMINISTRACIÓN está bloqueado para el rol islero; sus
        // filas no se tocan si quién guarda no tiene permiso de administración.
        if (! $usuario->isIslero()) {
            $turno->recaudosAdmin()->delete();
        }
    }

    /**
     * El traslado lo escribe el navegador como número plano (ej. "-98000" o
     * "-98000.00"). Leerlo como dinero borraba el punto decimal y multiplicaba
     * el valor por 100 en cada guardado.
     */
    private function trasladoValue(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return is_numeric($value) ? round((float) $value) : NumberParser::money($value);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function saveVentas(Turno $turno, array $rows): void
    {
        $total = collect($rows)->sum(function (array $row) use ($turno): float {
            $precio = ($row['combustible'] ?? null) === 'ACPM'
                ? $turno->precio_acpm
                : $turno->precio_corriente;

            return NumberParser::quantity($row['galones'] ?? null) * $precio;
        });

        foreach ($rows as $row) {
            $galones = NumberParser::quantity($row['galones'] ?? null);
            $surtidor = trim($row['surtidor'] ?? '');
            $combustible = $row['combustible'] ?? null;

            if (! $surtidor && ! $combustible) {
                continue;
            }

            $precio = $combustible === 'ACPM'
                ? $turno->precio_acpm
                : $turno->precio_corriente;

            $turno->ventas()->create([
                'surtidor' => $surtidor,
                'combustible' => $combustible,
                'galones' => $galones,
                'valor' => $galones * $precio,
                'total' => $total,
            ]);
        }
    }

    /**
     * Recompute and persist the "venta en tirillas de cortes" aggregates and
     * the grand total from the rows just saved, para que informes puedan
     * consultarlos directamente desde turnos sin recalcular turno_ventas.
     */
    private function saveVentasTotales(Turno $turno): void
    {
        $ventas = $turno->ventas()->get();

        $galonesCte = (float) $ventas->where('combustible', 'CTE')->sum('galones');
        $galonesAcpm = (float) $ventas->where('combustible', 'ACPM')->sum('galones');

        $turno->update([
            'tirillas_galones_corriente' => $galonesCte,
            'tirillas_galones_acpm' => $galonesAcpm,
            'tirillas_valor_corriente' => $galonesCte * $turno->precio_corriente,
            'tirillas_valor_acpm' => $galonesAcpm * $turno->precio_acpm,
            'total_ventas' => (float) $ventas->sum('valor'),
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function saveLecturas(Turno $turno, array $rows): void
    {
        $total = collect($rows)->sum(function (array $row): float {
            $inicial = NumberParser::quantity($row['lectura_inicial'] ?? null);
            $final = NumberParser::quantity($row['lectura_final'] ?? null);

            return max(0, $final - $inicial);
        });

        foreach ($rows as $row) {
            $inicial = NumberParser::quantity($row['lectura_inicial'] ?? null);
            $final = NumberParser::quantity($row['lectura_final'] ?? null);
            $galones = max(0, $final - $inicial);
            $manguera = trim($row['manguera'] ?? '');
            $combustible = $row['combustible'] ?? null;

            if (! $manguera) {
                continue;
            }

            $turno->surtidores()->create([
                'manguera' => $manguera,
                'combustible' => $combustible,
                'lectura_inicial' => $inicial,
                'lectura_final' => $final,
                'galones' => $galones,
                'total' => $total,
            ]);
        }
    }

    /**
     * Recompute and persist the "venta según lecturas" aggregates (galones y
     * valor por CORRIENTE/ACPM y total) desde turno_surtidores, para que
     * informes puedan consultarlos directamente desde el turno.
     */
    private function saveVentaLecturasTotales(Turno $turno): void
    {
        $surtidores = $turno->surtidores()->get();

        $galonesCorriente = (float) $surtidores->where('combustible', 'corriente')->sum('galones');
        $galonesAcpm = (float) $surtidores->where('combustible', 'acpm')->sum('galones');
        $valorCorriente = $galonesCorriente * $turno->precio_corriente;
        $valorAcpm = $galonesAcpm * $turno->precio_acpm;

        $turno->update([
            'lecturas_galones_corriente' => $galonesCorriente,
            'lecturas_galones_acpm' => $galonesAcpm,
            'lecturas_valor_corriente' => $valorCorriente,
            'lecturas_valor_acpm' => $valorAcpm,
            'total_venta_lecturas' => $valorCorriente + $valorAcpm,
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function saveUreaLubricantes(Turno $turno, array $rows): void
    {
        foreach ($rows as $row) {
            $cantidad = (float) ($row['cantidad'] ?? 0);
            $producto = $row['producto'] ?? null;
            $valorSinIva = NumberParser::money($row['valor_sin_iva'] ?? null);
            $iva = NumberParser::money($row['iva'] ?? null);
            $total = NumberParser::money($row['total'] ?? ($cantidad * ($valorSinIva + $iva)));

            // Sin producto no hay venta de canastilla; con producto se conserva aunque la cantidad sea 0.
            if (! $producto) {
                continue;
            }

            $turno->lubricantes()->create([
                'cantidad' => $cantidad,
                'producto' => $producto,
                'valor_sin_iva' => $valorSinIva,
                'iva' => $iva,
                'total' => $total,
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function saveConsignaciones(Turno $turno, array $rows): void
    {
        $values = collect($rows)
            ->reject(fn (array $row): bool => FilaPlanilla::vacia($row, ['consignacion_no', 'consignacion_valor']))
            ->map(fn (array $row): array => [
                'consignacion_no' => $row['consignacion_no'] ?? null,
                'valor' => NumberParser::money($row['consignacion_valor'] ?? null),
            ])->values();
        $total = $values->sum('valor');

        foreach ($values as $row) {
            $turno->consignaciones()->create([
                'consignacion_no' => $row['consignacion_no'],
                'valor' => $row['valor'],
                'total' => $total,
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function saveDescuentos(Turno $turno, array $rows): void
    {
        $values = collect($rows)
            ->reject(fn (array $row): bool => FilaPlanilla::vacia($row, ['descuento_no', 'descuento']))
            ->map(fn (array $row): array => [
                'descuento_no' => $row['descuento_no'] ?? null,
                'valor' => NumberParser::money($row['descuento'] ?? null),
            ])->values();
        $total = $values->sum('valor');

        foreach ($values as $row) {
            $turno->descuentos()->create([
                'descuento_no' => $row['descuento_no'],
                'valor' => $row['valor'],
                'total' => $total,
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function saveCartera(Turno $turno, array $rows): void
    {
        $values = collect($rows)
            ->reject(fn (array $row): bool => FilaPlanilla::vacia($row, ['cartera_factura_no', 'cliente_id', 'cartera_valor']))
            ->map(fn (array $row): array => [
                'factura_no' => $row['cartera_factura_no'] ?? null,
                'cliente_id' => ($row['cliente_id'] ?? null) ?: null,
                'valor' => NumberParser::money($row['cartera_valor'] ?? null),
            ])->values();
        $total = $values->sum('valor');

        foreach ($values as $row) {
            $turno->cartera()->create([
                'factura_no' => $row['factura_no'],
                'cliente_id' => $row['cliente_id'],
                'valor' => $row['valor'],
                'total' => $total,
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function saveQrPagos(Turno $turno, array $rows): void
    {
        // El concepto trae un valor por defecto en las filas nuevas: la fila cuenta si tiene valor.
        $values = collect($rows)
            ->reject(fn (array $row): bool => FilaPlanilla::vacia($row, ['valor']))
            ->map(fn (array $row): array => [
                'concepto' => $row['concepto'] ?? null,
                'valor' => NumberParser::money($row['valor'] ?? null),
            ])->values();
        $total = $values->sum('valor');

        foreach ($values as $row) {
            $turno->qrPagos()->create([
                'concepto' => $row['concepto'],
                'valor' => $row['valor'],
                'total' => $total,
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function saveRecaudos(Turno $turno, array $rows): void
    {
        $total = collect($rows)->sum(fn (array $row): float => NumberParser::money($row['valor'] ?? null));

        foreach ($rows as $row) {
            $valor = NumberParser::money($row['valor'] ?? null);
            $clienteId = ($row['cliente_id'] ?? null) ?: null;

            if (FilaPlanilla::vacia($row, ['cliente_id', 'valor'])) {
                continue;
            }

            $turno->recaudos()->create([
                'cliente_id' => $clienteId,
                'valor' => $valor,
                'total' => $total,
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function saveTransferencias(Turno $turno, array $rows): void
    {
        $totalPuntos = collect($rows)->sum(fn (array $row): float => NumberParser::money($row['puntos'] ?? null));

        foreach ($rows as $row) {
            $puntos = NumberParser::money($row['puntos'] ?? null);

            if (FilaPlanilla::vacia($row, ['puntos'])) {
                continue;
            }

            $turno->transferencias()->create([
                'puntos_redimidos' => $puntos,
                'total_puntos' => $totalPuntos,
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function saveGasolinaEds(Turno $turno, array $rows): void
    {
        $total = collect($rows)->sum(fn (array $row): float => NumberParser::money($row['puntos'] ?? null));

        foreach ($rows as $row) {
            $valor = NumberParser::money($row['puntos'] ?? null);

            if (FilaPlanilla::vacia($row, ['puntos'])) {
                continue;
            }

            $turno->gasolinaEds()->create([
                'valor' => $valor,
                'total' => $total,
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function saveVarios(Turno $turno, array $rows): void
    {
        $total = collect($rows)->sum(fn (array $row): float => NumberParser::money($row['valor'] ?? null));

        foreach ($rows as $row) {
            $concepto = trim((string) ($row['concepto'] ?? ''));
            $valor = NumberParser::money($row['valor'] ?? null);

            if (FilaPlanilla::vacia($row, ['concepto', 'valor'])) {
                continue;
            }

            $turno->varios()->create([
                'concepto' => $concepto,
                'valor' => $valor,
                'total' => $total,
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function saveRecaudosAdmin(Turno $turno, array $rows): void
    {
        $total = collect($rows)->sum(fn (array $row): float => NumberParser::money($row['valor'] ?? null));

        foreach ($rows as $row) {
            $banco = $row['banco'] ?? null;
            $responsableId = ($row['responsable_id'] ?? null) ?: null;
            $valor = NumberParser::money($row['valor'] ?? null);

            if (FilaPlanilla::vacia($row, ['banco', 'responsable_id', 'valor'])) {
                continue;
            }

            $turno->recaudosAdmin()->create([
                'banco' => $banco,
                'responsable_id' => $responsableId,
                'valor' => $valor,
                'total' => $total,
            ]);
        }
    }
}
