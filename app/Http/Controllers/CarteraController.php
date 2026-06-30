<?php

namespace App\Http\Controllers;

use App\Models\CarteraMovimiento;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CarteraController extends Controller
{
    public function index()
    {
        $customers = Customer::orderBy('name', 'asc')->get();

        return view('cartera.index', compact('customers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'saldo_inicial' => ['nullable', 'string', 'max:40'],
            'fecha_inicial' => ['nullable', 'date'],
            'fecha_final' => ['nullable', 'date'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.planillas' => ['nullable', 'string', 'max:40'],
            'detalles.*.fecha' => ['nullable', 'date'],
            'detalles.*.factura' => ['nullable', 'string', 'max:50'],
            'detalles.*.placas' => ['nullable', 'string', 'max:40'],
            'detalles.*.producto' => ['nullable', 'string', 'max:60'],
            'detalles.*.galones' => ['nullable', 'string', 'max:40'],
            'detalles.*.vr_unitario' => ['nullable', 'string', 'max:40'],
            'detalles.*.bruto' => ['nullable', 'string', 'max:40'],
            'detalles.*.descuento' => ['nullable', 'string', 'max:40'],
            'detalles.*.concepto' => ['nullable', 'string', 'max:150'],
            'detalles.*.tercero' => ['nullable', 'string', 'max:180'],
            'detalles.*.nit' => ['nullable', 'string', 'max:60'],
            'detalles.*.vr_neto_cargo' => ['nullable', 'string', 'max:40'],
            'detalles.*.abonos' => ['nullable', 'string', 'max:40'],
        ]);

        DB::transaction(function () use ($request) {
            $customerId = (int) $request->input('customer_id');
            $saldoInicial = round($this->parseDecimal($request->input('saldo_inicial')), 0);
            $fechaInicial = $request->input('fecha_inicial');
            $fechaFinal = $request->input('fecha_final');
            $saldoActual = $saldoInicial;

            foreach ($request->input('detalles', []) as $row) {
                $planillas = trim((string) ($row['planillas'] ?? ''));
                $fecha = $row['fecha'] ?? null;
                $factura = trim((string) ($row['factura'] ?? ''));
                $placas = trim((string) ($row['placas'] ?? ''));
                $producto = trim((string) ($row['producto'] ?? ''));
                $galones = $this->parseDecimal($row['galones'] ?? null);
                $vrUnitario = round($this->parseDecimal($row['vr_unitario'] ?? null), 0);
                $brutoInput = round($this->parseDecimal($row['bruto'] ?? null), 0);
                $descuento = round($this->parseDecimal($row['descuento'] ?? null), 0);
                $cuenta = trim((string) ($row['cuenta'] ?? ''));
                $concepto = trim((string) ($row['concepto'] ?? ''));
                $tercero = trim((string) ($row['tercero'] ?? ''));
                $nit = trim((string) ($row['nit'] ?? ''));
                $vrNetoCargoInput = round($this->parseDecimal($row['vr_neto_cargo'] ?? null), 0);
                $abonos = round($this->parseDecimal($row['abonos'] ?? null), 0);

                if (
                    $planillas === '' && ! $fecha && $factura === '' && $placas === '' && $producto === '' &&
                    $galones === 0.0 && $vrUnitario === 0.0 && $brutoInput === 0.0 && $descuento === 0.0 &&
                    $cuenta === '' && $concepto === '' && $tercero === '' && $nit === '' &&
                    $vrNetoCargoInput === 0.0 && $abonos === 0.0
                ) {
                    continue;
                }

                $bruto = $brutoInput > 0 ? $brutoInput : round($galones * $vrUnitario, 0);
                $vrNetoCargo = $vrNetoCargoInput > 0 ? $vrNetoCargoInput : max(0, $bruto - $descuento);
                $saldoActual = $saldoActual + $vrNetoCargo - $abonos;

                CarteraMovimiento::create([
                    'customer_id' => $customerId,
                    'saldo_inicial' => $saldoInicial,
                    'fecha_inicial' => $fechaInicial,
                    'fecha_final' => $fechaFinal,
                    'planillas' => $planillas,
                    'fecha' => $fecha,
                    'factura' => $factura,
                    'placas' => $placas,
                    'producto' => $producto,
                    'galones' => $galones,
                    'vr_unitario' => $vrUnitario,
                    'bruto' => $bruto,
                    'descuento' => $descuento,
                    'vr_neto_cargo' => $vrNetoCargo,
                    'abonos' => $abonos,
                    'saldo' => $saldoActual,
                    'cuenta' => $cuenta,
                    'concepto' => $concepto,
                    'tercero' => $tercero,
                    'nit' => $nit,
                    'debito' => 0,
                    'credito' => 0,
                ]);
            }
        });

        return redirect()->route('cartera.index')
            ->with('success', 'Cartera guardada correctamente.');
    }

    private function parseDecimal(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        $clean = str_replace(['.', ' '], ['', ''], (string) $value);
        $clean = str_replace(',', '.', $clean);

        return is_numeric($clean) ? (float) $clean : 0.0;
    }
}
