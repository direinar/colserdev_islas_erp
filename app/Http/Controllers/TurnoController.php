<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Lubricant;
use App\Models\Turno;
use App\Support\NumberParser;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TurnoController extends Controller
{
    public function create(Request $request)
    {
        $lubricants = Lubricant::orderBy('reference', 'asc')->get();
        $customers = Customer::orderBy('name', 'asc')->get();

        // Consecutivo global de turnos: no se reinicia por día, sigue subiendo
        // sin importar la fecha con la que se registre el siguiente turno.
        $today = Carbon::today()->toDateString();
        $row = DB::selectOne('select max(numero_turno) as max_turno from turnos');
        $last = $row->max_turno ?? 0;
        $nextNumber = (int) $last + 1;

        // Si vienen parámetros de búsqueda, cargar el turno
        $turno = null;
        $previousTurno = null;
        $searchFecha = $request->query('fecha');
        $searchNumero = $request->query('numero_turno');

        // Turnos ya registrados en la fecha consultada, para poblar el selector
        // de números de turno en el formulario de búsqueda (el consecutivo es
        // global, así que estos números no necesariamente inician en 1).
        $turnosDelDia = Turno::query()
            ->whereDate('fecha', $searchFecha ?: $today)
            ->orderBy('numero_turno')
            ->pluck('numero_turno');

        if ($searchFecha && $searchNumero) {
            // 'fecha' se guarda con hora (cast date -> datetime ISO), por lo que se
            // compara solo la parte de fecha para evitar fallos de coincidencia exacta.
            $turno = Turno::with(['ventas', 'surtidores', 'lubricantes', 'consignaciones', 'descuentos', 'cartera', 'qrPagos', 'recaudos', 'transferencias', 'gasolinaEds', 'varios', 'recaudosAdmin'])
                ->whereDate('fecha', $searchFecha)
                ->where('numero_turno', $searchNumero)
                ->first();
        } else {
            // Si es un nuevo turno, cargar el último turno registrado (sin importar
            // la fecha) para precargar las lecturas iniciales con sus finales.
            $previousTurno = Turno::with('surtidores')
                ->orderByDesc('numero_turno')
                ->first();
        }

        return view('planillas.turnos.create', compact('lubricants', 'customers', 'nextNumber', 'turno', 'previousTurno', 'turnosDelDia'));
    }

    public function store(Request $request)
    {
        $request->validate([
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
        ]);

        DB::transaction(function () use ($request) {
            // Si ya existe un turno para esa fecha + numero_turno, se actualiza en vez de duplicar
            // 'fecha' se guarda con hora (cast date -> datetime ISO), por lo que se
            // compara solo la parte de fecha para detectar correctamente el turno existente
            // y actualizarlo en vez de intentar crear uno duplicado (violaría el unique fecha+numero_turno).
            $turno = Turno::query()
                ->whereDate('fecha', $request->input('fecha'))
                ->where('numero_turno', $request->input('numero_turno'))
                ->first();

            // Una planilla ya revisada solo puede volver a modificarla el administrador
            if ($turno && $turno->revisado && ! $request->user()->isAdministrador()) {
                abort(403, 'Esta planilla ya fue revisada. Solo un administrador puede modificarla.');
            }

            $atributos = [
                'fecha' => $request->input('fecha'),
                'numero_turno' => $request->input('numero_turno'),
                'nombre_vendedor' => $request->user()->name,
                'precio_corriente' => config('combustibles.corriente'),
                'precio_acpm' => config('combustibles.acpm'),
            ];

            if ($turno) {
                $turno->update($atributos);
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
                $turno->recaudosAdmin()->delete();
            } else {
                $turno = Turno::create($atributos);
            }

            $this->saveVentas($turno, $request->input('ventas', []));
            $this->saveLecturas($turno, $request->input('lecturas', []));
            $this->saveUreaLubricantes($turno, $request->input('urea_lubricantes', []));
            $this->saveConsignaciones($turno, $request->input('consignaciones', []));
            $this->saveDescuentos($turno, $request->input('descuentos', []));
            $this->saveCartera($turno, $request->input('cartera', []));
            $this->saveQrPagos($turno, $request->input('qr_pagos', []));
            $this->saveRecaudos($turno, $request->input('recaudos', []));
            $this->saveTransferencias($turno, $request->input('transferencias', []));
            $this->saveGasolinaEds($turno, $request->input('gasolina_eds', []));
            $this->saveVarios($turno, $request->input('varios', []));
            $this->saveRecaudosAdmin($turno, $request->input('recaudos_admin', []));
            $this->saveVentasTotales($turno);
            $this->saveVentaLecturasTotales($turno);
        });

        return redirect()->route('turnos.create', [
            'fecha' => $request->input('fecha'),
            'numero_turno' => $request->input('numero_turno'),
        ])
            ->with('success', 'Turno guardado correctamente.');
    }

    public function pendientes(Request $request)
    {
        $turnos = Turno::query()
            ->where('revisado', false)
            ->orderByDesc('fecha')
            ->orderByDesc('numero_turno')
            ->paginate(20);

        return view('planillas.turnos.pendientes', compact('turnos'));
    }

    public function revisar(Request $request, Turno $turno)
    {
        $turno->update([
            'revisado' => true,
            'revisado_por' => $request->user()->name,
            'revisado_at' => now(),
        ]);

        return back()->with('success', 'Turno #'.$turno->numero_turno.' del '.$turno->fecha->format('Y-m-d').' marcado como revisado.');
    }

    private function saveVentas(Turno $turno, array $rows): void
    {
        foreach ($rows as $row) {
            $galones = NumberParser::quantity($row['galones'] ?? null);
            $surtidor = trim($row['surtidor'] ?? '');
            $combustible = $row['combustible'] ?? null;

            if (! $surtidor && ! $combustible) {
                continue;
            }

            $precio = $combustible === 'ACPM'
                ? config('combustibles.acpm')
                : config('combustibles.corriente');

            $turno->ventas()->create([
                'surtidor' => $surtidor,
                'combustible' => $combustible,
                'galones' => $galones,
                'valor' => $galones * $precio,
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

    private function saveLecturas(Turno $turno, array $rows): void
    {
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

    private function saveUreaLubricantes(Turno $turno, array $rows): void
    {
        foreach ($rows as $row) {
            $cantidad = (int) ($row['cantidad'] ?? 0);
            $producto = $row['producto'] ?? null;
            $valorSinIva = NumberParser::quantity($row['valor_sin_iva'] ?? null);
            $iva = NumberParser::quantity($row['iva'] ?? null);
            $total = NumberParser::quantity($row['total'] ?? ($cantidad * ($valorSinIva + $iva)));

            if (! $producto || $cantidad <= 0) {
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

    private function saveConsignaciones(Turno $turno, array $rows): void
    {
        $values = collect($rows)->map(fn (array $row): array => [
            'consignacion_no' => $row['consignacion_no'] ?? null,
            'valor' => NumberParser::money($row['consignacion_valor'] ?? null),
        ])->filter(fn (array $row): bool => $row['valor'] !== 0.0 || $row['consignacion_no'])->values();
        $total = $values->sum('valor');

        foreach ($values as $row) {
            $turno->consignaciones()->create([
                'consignacion_no' => $row['consignacion_no'],
                'valor' => $row['valor'],
                'total' => $total,
            ]);
        }
    }

    private function saveDescuentos(Turno $turno, array $rows): void
    {
        $values = collect($rows)->map(fn (array $row): array => [
            'descuento_no' => $row['descuento_no'] ?? null,
            'valor' => NumberParser::money($row['descuento'] ?? null),
        ])->filter(fn (array $row): bool => $row['valor'] !== 0.0 || $row['descuento_no'])->values();
        $total = $values->sum('valor');

        foreach ($values as $row) {
            $turno->descuentos()->create([
                'descuento_no' => $row['descuento_no'],
                'valor' => $row['valor'],
                'total' => $total,
            ]);
        }
    }

    private function saveCartera(Turno $turno, array $rows): void
    {
        $values = collect($rows)->map(fn (array $row): array => [
            'factura_no' => $row['cartera_factura_no'] ?? null,
            'cliente_id' => $row['cliente_id'] ?? null,
            'valor' => NumberParser::money($row['cartera_valor'] ?? null),
        ])->filter(fn (array $row): bool => $row['valor'] !== 0.0 || $row['factura_no'] || $row['cliente_id'])->values();
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

    private function saveQrPagos(Turno $turno, array $rows): void
    {
        foreach ($rows as $row) {
            $valor = NumberParser::money($row['valor'] ?? null);
            $concepto = null;

            foreach ($row as $key => $value) {
                if ($key !== 'valor') {
                    $concepto = $value;
                    break;
                }
            }

            if (! $concepto || $valor <= 0) {
                continue;
            }

            $turno->qrPagos()->create([
                'concepto' => $concepto,
                'valor' => $valor,
            ]);
        }
    }

    private function saveRecaudos(Turno $turno, array $rows): void
    {
        foreach ($rows as $row) {
            $valor = NumberParser::money($row['valor'] ?? null);
            $clienteId = $row['cliente_id'] ?? null;

            if ($valor <= 0) {
                continue;
            }

            $turno->recaudos()->create([
                'cliente_id' => $clienteId,
                'valor' => $valor,
            ]);
        }
    }

    private function saveTransferencias(Turno $turno, array $rows): void
    {
        foreach ($rows as $row) {
            $valor = NumberParser::money($row['valor'] ?? null);
            $puntos = NumberParser::money($row['puntos'] ?? null);

            if ($valor === 0 && $puntos === 0) {
                continue;
            }

            $turno->transferencias()->create([
                'valor' => $valor,
                'puntos_redimidos' => $puntos,
            ]);
        }
    }

    private function saveGasolinaEds(Turno $turno, array $rows): void
    {
        foreach ($rows as $row) {
            $valor = NumberParser::money($row['puntos'] ?? null);

            if ($valor <= 0) {
                continue;
            }

            $turno->gasolinaEds()->create([
                'valor' => $valor,
            ]);
        }
    }

    private function saveVarios(Turno $turno, array $rows): void
    {
        foreach ($rows as $row) {
            $concepto = $row['concepto'] ?? null;
            $valor = NumberParser::money($row['valor'] ?? null);

            if (! $concepto || $valor <= 0) {
                continue;
            }

            $turno->varios()->create([
                'concepto' => $concepto,
                'valor' => $valor,
            ]);
        }
    }

    private function saveRecaudosAdmin(Turno $turno, array $rows): void
    {
        foreach ($rows as $row) {
            $banco = $row['banco'] ?? null;
            $responsableId = $row['responsable_id'] ?? null;
            $valor = NumberParser::money($row['valor'] ?? null);

            if (! $banco && ! $responsableId && $valor <= 0) {
                continue;
            }

            $turno->recaudosAdmin()->create([
                'banco' => $banco,
                'responsable_id' => $responsableId,
                'valor' => $valor,
            ]);
        }
    }
}
