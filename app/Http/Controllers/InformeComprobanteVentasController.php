<?php

namespace App\Http\Controllers;

use App\Concerns\ResuelveRangoFechas;
use App\Models\ComprobanteVentaCuenta;
use App\Services\ComprobanteVentasExcelService;
use App\Services\ControlCajaService;
use App\Support\DescargaExcel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Comprobante contable de ventas del periodo (antes, la tabla de abajo del informe
 * Control caja) y sus cuentas contables.
 */
class InformeComprobanteVentasController extends Controller
{
    use ResuelveRangoFechas;

    public function index(Request $request, ControlCajaService $controlCaja): View
    {
        [$desde, $hasta] = $this->rango($request);

        return view('informes.comprobante-ventas.index', [
            'desde' => $desde,
            'hasta' => $hasta,
        ] + $controlCaja->comprobanteVentas($desde, $hasta));
    }

    public function exportar(Request $request, ComprobanteVentasExcelService $excel): StreamedResponse
    {
        [$desde, $hasta] = $this->rango($request);

        $libro = $excel->generar($desde, $hasta);

        return DescargaExcel::respuesta($libro, 'comprobante-ventas-'.$desde.'-a-'.$hasta.'.xlsx');
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

        return redirect()->route('informes.comprobante-ventas.index', $request->only('desde', 'hasta'))
            ->with('success', 'Cuentas contables guardadas correctamente.');
    }
}
