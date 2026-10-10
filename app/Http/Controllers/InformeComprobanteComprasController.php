<?php

namespace App\Http\Controllers;

use App\Models\ComprobanteCompraCuenta;
use App\Models\Proveedor;
use App\Services\ComprobanteContableComprasExcelService;
use App\Services\ComprobanteContableComprasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InformeComprobanteComprasController extends Controller
{
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

        return response()->streamDownload(
            fn () => (new Xlsx($libro))->save('php://output'),
            'comprobante-contable-compras-'.$desde.'-a-'.$hasta.'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
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

    /**
     * Por defecto, el mes en curso hasta hoy.
     *
     * @return array{0: string, 1: string}
     */
    private function rango(Request $request): array
    {
        $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ]);

        $desde = Carbon::parse($request->query('desde') ?: now()->startOfMonth());
        $hasta = Carbon::parse($request->query('hasta') ?: now());

        if ($hasta->lt($desde)) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        return [$desde->format('Y-m-d'), $hasta->format('Y-m-d')];
    }
}
