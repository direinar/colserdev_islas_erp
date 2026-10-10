<?php

namespace App\Http\Controllers;

use App\Concerns\ResuelveRangoFechas;
use App\Models\ComprobanteCompraCuenta;
use App\Models\Proveedor;
use App\Services\ComprobanteContableComprasExcelService;
use App\Services\ComprobanteContableComprasService;
use App\Support\DescargaExcel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InformeComprobanteComprasController extends Controller
{
    use ResuelveRangoFechas;

    public function index(Request $request, ComprobanteContableComprasService $comprobante): View
    {
        [$desde, $hasta] = $this->rango($request);

        return view('informes.comprobante-contable-compras.index', [
            'desde' => $desde,
            'hasta' => $hasta,
            'proveedores' => Proveedor::query()->orderBy('name')->get(['id', 'name']),
        ] + $comprobante->generar($desde, $hasta));
    }

    public function exportar(Request $request, ComprobanteContableComprasExcelService $excel): StreamedResponse
    {
        [$desde, $hasta] = $this->rango($request);

        $libro = $excel->generar($desde, $hasta);

        return DescargaExcel::respuesta($libro, 'comprobante-contable-compras-'.$desde.'-a-'.$hasta.'.xlsx');
    }

    public function guardarCuentas(Request $request): RedirectResponse
    {
        $request->validate([
            'cuentas' => ['required', 'array'],
            'cuentas.*.cuenta' => ['required', 'string', 'max:30'],
            'proveedor_combustibles_id' => ['nullable', 'exists:proveedores,id'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ]);

        DB::transaction(function () use ($request): void {
            foreach ($request->input('cuentas', []) as $clave => $datos) {
                ComprobanteCompraCuenta::query()
                    ->where('clave', (string) $clave)
                    ->update(['cuenta' => trim((string) $datos['cuenta'])]);
            }

            ComprobanteCompraCuenta::query()
                ->where('clave', ComprobanteCompraCuenta::PROVEEDOR_COMBUSTIBLES)
                ->update(['proveedor_id' => $request->input('proveedor_combustibles_id') ?: null]);
        });

        return redirect()->route('informes.comprobante-contable-compras.index', $request->only('desde', 'hasta'))
            ->with('success', 'Cuentas contables guardadas correctamente.');
    }
}
