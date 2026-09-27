<?php

namespace App\Http\Controllers;

use App\Models\CarteraMovimiento;
use App\Models\Customer;
use App\Services\CarteraSaldoService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CarteraController extends Controller
{
    /**
     * Campos que se pueden completar en cartera para los movimientos que
     * vienen de la planilla de turno. Planilla, fecha, factura, valor y
     * concepto (venta a crédito) los fija la planilla.
     */
    private const CAMPOS_EDITABLES_TURNO = [
        'placas', 'producto', 'galones', 'vr_unitario', 'cuenta', 'tercero', 'nit', 'abonos',
    ];

    public function index(Request $request, CarteraSaldoService $saldos)
    {
        $customers = Customer::orderBy('name', 'asc')->get();

        $fechaInicial = $request->query('fecha_inicial') ?: Carbon::today()->startOfMonth()->toDateString();
        $fechaFinal = $request->query('fecha_final') ?: Carbon::today()->endOfMonth()->toDateString();
        $customer = $customers->firstWhere('id', (int) $request->query('customer_id'));

        $movimientos = collect();
        $saldoAnterior = 0.0;

        if ($customer) {
            $saldoAnterior = $saldos->saldoAnterior($customer->id, $fechaInicial);

            $movimientos = $saldos->ordenar(
                CarteraMovimiento::query()
                    ->where('customer_id', $customer->id)
                    ->whereDate('fecha', '>=', $fechaInicial)
                    ->whereDate('fecha', '<=', $fechaFinal)
                    ->get()
            );
        }

        return view('cartera.index', compact('customers', 'customer', 'movimientos', 'saldoAnterior', 'fechaInicial', 'fechaFinal'));
    }

    public function store(Request $request, CarteraSaldoService $saldos)
    {
        $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'fecha_inicial' => ['nullable', 'date'],
            'fecha_final' => ['nullable', 'date'],
            'eliminar' => ['nullable', 'array'],
            'eliminar.*' => ['integer'],
            'detalles' => ['nullable', 'array'],
            'detalles.*.id' => ['nullable', 'integer'],
            'detalles.*.planillas' => ['nullable', 'string', 'max:40'],
            'detalles.*.fecha' => ['nullable', 'date'],
            'detalles.*.factura' => ['nullable', 'string', 'max:50'],
            'detalles.*.placas' => ['nullable', 'string', 'max:40'],
            'detalles.*.producto' => ['nullable', 'string', 'max:60'],
            'detalles.*.galones' => ['nullable', 'string', 'max:40'],
            'detalles.*.vr_unitario' => ['nullable', 'string', 'max:40'],
            'detalles.*.descuento' => ['nullable', 'string', 'max:40'],
            'detalles.*.cuenta' => ['nullable', 'string', 'max:50'],
            'detalles.*.concepto' => ['nullable', 'string', 'max:150'],
            'detalles.*.tercero' => ['nullable', 'string', 'max:180'],
            'detalles.*.nit' => ['nullable', 'string', 'max:60'],
            'detalles.*.abonos' => ['nullable', 'string', 'max:40'],
        ]);

        $customerId = (int) $request->input('customer_id');

        DB::transaction(function () use ($request, $customerId, $saldos) {
            // Solo se eliminan movimientos manuales; los de planilla se quitan desde el turno.
            CarteraMovimiento::query()
                ->where('customer_id', $customerId)
                ->whereNull('turno_id')
                ->whereIn('id', $request->input('eliminar', []))
                ->delete();

            foreach ($request->input('detalles', []) as $index => $row) {
                $datos = $this->datosFila($row);

                if (! empty($row['id'])) {
                    $movimiento = CarteraMovimiento::query()
                        ->where('customer_id', $customerId)
                        ->findOrFail((int) $row['id']);

                    $movimiento->update($movimiento->esDeTurno()
                        ? array_intersect_key($datos, array_flip(self::CAMPOS_EDITABLES_TURNO))
                        : $datos);

                    continue;
                }

                if ($this->filaVacia($datos)) {
                    continue;
                }

                if (! $datos['fecha']) {
                    throw ValidationException::withMessages([
                        "detalles.$index.fecha" => 'La fecha es obligatoria en los movimientos manuales.',
                    ]);
                }

                CarteraMovimiento::create($datos + ['customer_id' => $customerId]);
            }

            $saldos->recalcular($customerId);
        });

        return redirect()->route('cartera.index', [
            'customer_id' => $customerId,
            'fecha_inicial' => $request->input('fecha_inicial'),
            'fecha_final' => $request->input('fecha_final'),
        ])->with('success', 'Cartera guardada correctamente.');
    }

    /**
     * @return array<string, mixed>
     */
    private function datosFila(array $row): array
    {
        $galones = $this->parseDecimal($row['galones'] ?? null);
        $vrUnitario = round($this->parseDecimal($row['vr_unitario'] ?? null), 0);
        $descuento = round($this->parseDecimal($row['descuento'] ?? null), 0);
        $bruto = round($galones * $vrUnitario, 0);

        return [
            'planillas' => trim((string) ($row['planillas'] ?? '')),
            'fecha' => $row['fecha'] ?? null,
            'factura' => trim((string) ($row['factura'] ?? '')),
            'placas' => trim((string) ($row['placas'] ?? '')),
            'producto' => trim((string) ($row['producto'] ?? '')),
            'galones' => $galones,
            'vr_unitario' => $vrUnitario,
            'bruto' => $bruto,
            'descuento' => $descuento,
            'vr_neto_cargo' => max(0, $bruto - $descuento),
            'cuenta' => trim((string) ($row['cuenta'] ?? '')),
            'concepto' => trim((string) ($row['concepto'] ?? '')),
            'tercero' => trim((string) ($row['tercero'] ?? '')),
            'nit' => trim((string) ($row['nit'] ?? '')),
            'abonos' => round($this->parseDecimal($row['abonos'] ?? null), 0),
        ];
    }

    private function filaVacia(array $datos): bool
    {
        foreach ($datos as $valor) {
            if ($valor !== '' && $valor !== null && $valor !== 0.0 && $valor !== 0) {
                return false;
            }
        }

        return true;
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
