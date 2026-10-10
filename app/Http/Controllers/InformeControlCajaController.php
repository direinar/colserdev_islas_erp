<?php

namespace App\Http\Controllers;

use App\Models\ComprobanteVentaCuenta;
use App\Services\ControlCajaExcelService;
use App\Services\ControlCajaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InformeControlCajaController extends Controller
{
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

        return response()->streamDownload(
            fn () => (new Xlsx($libro))->save('php://output'),
            'control-caja-'.$desde.'-a-'.$hasta.'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
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
