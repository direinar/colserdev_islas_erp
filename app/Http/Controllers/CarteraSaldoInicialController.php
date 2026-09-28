<?php

namespace App\Http\Controllers;

use App\Models\CarteraMovimiento;
use App\Models\Customer;
use App\Services\CarteraSaldoInicialService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Saldos iniciales de cartera (carga única al arrancar el sistema): uno por
 * cliente, positivo si debe y negativo si tiene saldo a favor.
 */
class CarteraSaldoInicialController extends Controller
{
    public function index(): View
    {
        $saldos = CarteraMovimiento::query()
            ->with('customer')
            ->where('origen', CarteraMovimiento::ORIGEN_SALDO_INICIAL)
            ->get()
            ->sortBy(fn (CarteraMovimiento $m): string => (string) $m->customer?->name)
            ->values();

        $customers = Customer::orderBy('name')->get();

        return view('cartera.saldos-iniciales.index', compact('saldos', 'customers'));
    }

    public function store(Request $request, CarteraSaldoInicialService $service): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => [
                'required',
                'exists:customers,id',
                Rule::unique('cartera_movimientos', 'customer_id')->where('origen', CarteraMovimiento::ORIGEN_SALDO_INICIAL),
            ],
            'fecha' => ['required', 'date'],
            'valor' => ['required', 'string', 'max:40'],
        ], [
            'customer_id.unique' => 'El cliente ya tiene saldo inicial; edítelo en la lista.',
        ]);

        $service->registrar((int) $validated['customer_id'], $validated['fecha'], $service->valor($validated['valor']));

        return redirect()
            ->route('cartera-saldos-iniciales.index')
            ->with('success', 'Saldo inicial registrado correctamente.');
    }

    public function edit(CarteraMovimiento $saldoInicial): View
    {
        abort_unless($saldoInicial->esSaldoInicial(), 404);

        $saldoInicial->load('customer');

        return view('cartera.saldos-iniciales.edit', compact('saldoInicial'));
    }

    public function update(Request $request, CarteraMovimiento $saldoInicial, CarteraSaldoInicialService $service): RedirectResponse
    {
        abort_unless($saldoInicial->esSaldoInicial(), 404);

        $validated = $request->validate([
            'fecha' => ['required', 'date'],
            'valor' => ['required', 'string', 'max:40'],
        ]);

        $service->registrar((int) $saldoInicial->customer_id, $validated['fecha'], $service->valor($validated['valor']));

        return redirect()
            ->route('cartera-saldos-iniciales.index')
            ->with('success', 'Saldo inicial actualizado correctamente.');
    }

    public function destroy(CarteraMovimiento $saldoInicial, CarteraSaldoInicialService $service): RedirectResponse
    {
        abort_unless($saldoInicial->esSaldoInicial(), 404);

        $service->eliminar($saldoInicial);

        return redirect()
            ->route('cartera-saldos-iniciales.index')
            ->with('success', 'Saldo inicial eliminado correctamente.');
    }

    public function plantilla(CarteraSaldoInicialService $service): StreamedResponse
    {
        $libro = $service->plantilla();

        return response()->streamDownload(
            fn () => (new Xlsx($libro))->save('php://output'),
            'plantilla-saldos-iniciales.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    public function importar(Request $request, CarteraSaldoInicialService $service): RedirectResponse
    {
        $request->validate([
            'archivo' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        $resultado = $service->importar($request->file('archivo')->getRealPath());

        return redirect()
            ->route('cartera-saldos-iniciales.index')
            ->with('success', "Importación terminada: {$resultado['cargados']} saldo(s) cargado(s).")
            ->with('errores_importacion', $resultado['errores']);
    }
}
