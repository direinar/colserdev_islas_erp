<?php

namespace App\Http\Controllers;

use App\Concerns\ResuelveRangoFechas;
use App\Models\ComprobanteVentaCuenta;
use App\Services\ControlCajaExcelService;
use App\Services\ControlCajaService;
use App\Support\DescargaExcel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InformeControlCajaController extends Controller
{
    use ResuelveRangoFechas;

    public function index(Request $request, ControlCajaService $controlCaja): View
    {
        [$desde, $hasta] = $this->rango($request);

        return view('informes.control-caja.index', [
            'desde' => $desde,
            'hasta' => $hasta,
        ] + $controlCaja->informe($desde, $hasta));
    }

    public function exportar(Request $request, ControlCajaExcelService $excel): StreamedResponse
    {
        [$desde, $hasta] = $this->rango($request);

        $libro = $excel->generar($desde, $hasta);

        return DescargaExcel::respuesta($libro, 'control-caja-'.$desde.'-a-'.$hasta.'.xlsx');
    }

    public function guardarCuentas(Request $request): RedirectResponse
    {
        $request->validate([
            'cuentas' => ['required', 'array'],
            'cuentas.*.cuenta' => ['required', 'string', 'max:30'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ]);

        DB::transaction(function () use ($request): void {
            foreach ($request->input('cuentas', []) as $clave => $datos) {
                ComprobanteVentaCuenta::query()
                    ->where('clave', (string) $clave)
                    ->update(['cuenta' => trim((string) $datos['cuenta'])]);
            }
        });

        return redirect()->route('informes.control-caja.index', $request->only('desde', 'hasta'))
            ->with('success', 'Cuentas contables guardadas correctamente.');
    }
}
