<?php

namespace App\Http\Controllers;

use App\Models\CarteraMovimiento;
use App\Models\Customer;
use App\Services\CarteraEstadoCuentaExcelService;
use App\Services\CarteraSaldoService;
use App\Support\NumberParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ESTADO DE CUENTA del cliente. Los movimientos llegan solo desde la
 * planilla de turno (créditos y recaudos) o del saldo inicial; en cartera
 * únicamente se completan los datos del vehículo de las ventas a crédito.
 */
class CarteraController extends Controller
{
    public function index(Request $request, CarteraSaldoService $saldos): View
    {
        $customers = Customer::orderBy('name', 'asc')->get();

        [$fechaInicial, $fechaFinal] = $this->rango($request);
        $customer = $customers->firstWhere('id', (int) $request->query('customer_id'));

        $estado = $customer
            ? $saldos->estadoDeCuenta($customer->id, $fechaInicial, $fechaFinal)
            : ['saldoAnterior' => 0.0, 'movimientos' => collect(), 'totalCargos' => 0.0, 'totalAbonos' => 0.0, 'saldoFinal' => 0.0];

        return view('cartera.index', $estado + compact('customers', 'customer', 'fechaInicial', 'fechaFinal'));
    }

    /**
     * GUARDAR DATOS DEL VEHÍCULO: solo placas, producto, galones, vr. unitario
     * y descuento (informativo, no cambia el cargo ni el saldo) de las ventas
     * a crédito. Lo demás se modifica únicamente desde la planilla.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'fecha_inicial' => ['nullable', 'date'],
            'fecha_final' => ['nullable', 'date'],
            'detalles' => ['nullable', 'array'],
            'detalles.*.id' => ['required', 'integer'],
            'detalles.*.placas' => ['nullable', 'string', 'max:40'],
            'detalles.*.producto' => ['nullable', 'string', 'max:60'],
            'detalles.*.galones' => ['nullable', 'string', 'max:40'],
            'detalles.*.vr_unitario' => ['nullable', 'string', 'max:40'],
            'detalles.*.descuento' => ['nullable', 'string', 'max:40'],
        ]);

        $customerId = (int) $request->input('customer_id');

        DB::transaction(function () use ($request, $customerId): void {
            foreach ($request->input('detalles', []) as $row) {
                $movimiento = CarteraMovimiento::query()
                    ->where('customer_id', $customerId)
                    ->where('origen', CarteraMovimiento::ORIGEN_CREDITO_DIRECTO)
                    ->find((int) $row['id']);

                if (! $movimiento) {
                    continue;
                }

                $galones = $this->parseGalones($row['galones'] ?? null);
                $vrUnitario = round(NumberParser::money($row['vr_unitario'] ?? null), 0);

                $movimiento->update([
                    'placas' => trim((string) ($row['placas'] ?? '')),
                    'producto' => trim((string) ($row['producto'] ?? '')),
                    'galones' => $galones,
                    'vr_unitario' => $vrUnitario,
                    'bruto' => round($galones * $vrUnitario, 0),
                    'descuento' => round(NumberParser::money($row['descuento'] ?? null), 0),
                ]);
            }
        });

        return redirect()->route('cartera.index', [
            'customer_id' => $customerId,
            'fecha_inicial' => $request->input('fecha_inicial'),
            'fecha_final' => $request->input('fecha_final'),
        ])->with('success', 'Datos del vehículo guardados correctamente.');
    }

    public function exportar(Request $request, CarteraEstadoCuentaExcelService $excel): StreamedResponse
    {
        $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'fecha_inicial' => ['nullable', 'date'],
            'fecha_final' => ['nullable', 'date'],
        ]);

        $customer = Customer::findOrFail((int) $request->query('customer_id'));
        [$fechaInicial, $fechaFinal] = $this->rango($request);

        $libro = $excel->generar($customer, $fechaInicial, $fechaFinal);
        $archivo = 'estado-de-cuenta-'.Str::slug($customer->name).'-'.$fechaInicial.'-a-'.$fechaFinal.'.xlsx';

        return response()->streamDownload(
            fn () => (new Xlsx($libro))->save('php://output'),
            $archivo,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    /**
     * La pantalla de cartera muestra los galones en formato es-CO
     * (punto = miles, coma = decimales), p. ej. "1.500,25".
     */
    private function parseGalones(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        $clean = str_replace(',', '.', str_replace(['.', ' '], '', (string) $value));

        return is_numeric($clean) ? (float) $clean : 0.0;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function rango(Request $request): array
    {
        return [
            $request->query('fecha_inicial') ?: Carbon::today()->startOfMonth()->toDateString(),
            $request->query('fecha_final') ?: Carbon::today()->endOfMonth()->toDateString(),
        ];
    }
}
