<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Lubricant;
use App\Models\Turno;
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
            $turno = Turno::with(['ventas', 'surtidores', 'lubricantes', 'mediosPago', 'qrPagos', 'recaudos', 'transferencias', 'gasolinaEds', 'varios', 'recaudosAdmin'])
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
            'medios_pago' => ['nullable', 'array'],
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
                'nombre_vendedor' => $request->input('nombre_vendedor'),
                'precio_corriente' => config('combustibles.corriente'),
                'precio_acpm' => config('combustibles.acpm'),
                'traslado_sobrante' => 0,
                'traslado_faltante' => 0,
            ];

            if ($turno) {
                $turno->update($atributos);
                $turno->ventas()->delete();
                $turno->surtidores()->delete();
                $turno->lubricantes()->delete();
                $turno->mediosPago()->delete();
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
            $this->saveMediosPago($turno, $request->input('medios_pago', []));
            $this->saveQrPagos($turno, $request->input('qr_pagos', []));
            $this->saveRecaudos($turno, $request->input('recaudos', []));
            $this->saveTransferencias($turno, $request->input('transferencias', []));
            $this->saveGasolinaEds($turno, $request->input('gasolina_eds', []));
            $this->saveVarios($turno, $request->input('varios', []));
            $this->saveRecaudosAdmin($turno, $request->input('recaudos_admin', []));
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

    /**
     * Parse a decimal typed by the user in either format ("4,760" or "4.760")
     * without assuming '.' is always a thousands separator — that assumption
     * silently inflated values by 1000x whenever the browser submitted the raw
     * value before the JS blur handler had a chance to normalize it (e.g. the
     * user pressed Enter/submitted without blurring the field).
     */
    private function parseDecimal($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        $clean = trim((string) $value);
        $hasDot = str_contains($clean, '.');
        $hasComma = str_contains($clean, ',');

        if ($hasDot && $hasComma) {
            if (strrpos($clean, '.') > strrpos($clean, ',')) {
                // '.' is the decimal separator, ',' groups thousands.
                $clean = str_replace(',', '', $clean);
            } else {
                // ',' is the decimal separator, '.' groups thousands.
                $clean = str_replace('.', '', $clean);
                $clean = str_replace(',', '.', $clean);
            }
        } elseif ($hasComma) {
            // Multiple commas can only be thousands separators; a single
            // comma is the decimal separator (es-CO convention).
            $clean = substr_count($clean, ',') > 1
                ? str_replace(',', '', $clean)
                : str_replace(',', '.', $clean);
        } elseif ($hasDot && substr_count($clean, '.') > 1) {
            // Multiple dots can only be thousands separators.
            $clean = str_replace('.', '', $clean);
        }
        // A single dot is treated as the decimal separator and left as-is.

        return is_numeric($clean) ? (float) $clean : 0.0;
    }

    private function saveVentas(Turno $turno, array $rows): void
    {
        foreach ($rows as $row) {
            $galones = $this->parseDecimal($row['galones'] ?? null);
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

    private function saveLecturas(Turno $turno, array $rows): void
    {
        foreach ($rows as $row) {
            $inicial = $this->parseDecimal($row['lectura_inicial'] ?? null);
            $final = $this->parseDecimal($row['lectura_final'] ?? null);
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

    private function saveUreaLubricantes(Turno $turno, array $rows): void
    {
        foreach ($rows as $row) {
            $cantidad = (int) ($row['cantidad'] ?? 0);
            $producto = $row['producto'] ?? null;
            $valorSinIva = $this->parseDecimal($row['valor_sin_iva'] ?? null);
            $iva = $this->parseDecimal($row['iva'] ?? null);
            $total = $this->parseDecimal($row['total'] ?? ($cantidad * ($valorSinIva + $iva)));

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

    private function saveMediosPago(Turno $turno, array $rows): void
    {
        foreach ($rows as $row) {
            $consignacionValor = $this->parseDecimal($row['consignacion_valor'] ?? null);
            $descuento = $this->parseDecimal($row['descuento'] ?? null);
            $carteraValor = $this->parseDecimal($row['cartera_valor'] ?? null);
            $clienteId = $row['cliente_id'] ?? null;
            $consignacionNo = $row['consignacion_no'] ?? null;
            $carteraFacturaNo = $row['cartera_factura_no'] ?? null;

            if ($consignacionValor === 0 && $carteraValor === 0 && ! $consignacionNo && ! $carteraFacturaNo) {
                continue;
            }

            $turno->mediosPago()->create([
                'consignacion_no' => $consignacionNo,
                'consignacion_valor' => $consignacionValor,
                'descuento' => $descuento,
                'cartera_factura_no' => $carteraFacturaNo,
                'cliente_id' => $clienteId,
                'cartera_valor' => $carteraValor,
            ]);
        }
    }

    private function saveQrPagos(Turno $turno, array $rows): void
    {
        foreach ($rows as $row) {
            $valor = $this->parseDecimal($row['valor'] ?? null);
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
            $valor = $this->parseDecimal($row['valor'] ?? null);
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
            $valor = $this->parseDecimal($row['valor'] ?? null);
            $puntos = $this->parseDecimal($row['puntos'] ?? null);

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
            $valor = $this->parseDecimal($row['puntos'] ?? null);

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
            $valor = $this->parseDecimal($row['valor'] ?? null);

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
            $valor = $this->parseDecimal($row['valor'] ?? null);

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
