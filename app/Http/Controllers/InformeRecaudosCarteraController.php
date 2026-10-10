<?php

namespace App\Http\Controllers;

use App\Models\Banco;
use App\Models\ComprobanteRecaudoCuenta;
use App\Services\RecaudosCarteraExcelService;
use App\Services\RecaudosCarteraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InformeRecaudosCarteraController extends Controller
{
    public function index(Request $request, RecaudosCarteraService $recaudos): View
    {
        [$desde, $hasta] = $this->rango($request);

        return view('informes.recaudos-cartera.index', [
            'desde' => $desde,
            'hasta' => $hasta,
        ] + $recaudos->informe($desde, $hasta));
    }

    public function exportar(Request $request, RecaudosCarteraExcelService $excel): StreamedResponse
    {
        [$desde, $hasta] = $this->rango($request);

        $libro = $excel->generar($desde, $hasta);

        return response()->streamDownload(
            fn () => (new Xlsx($libro))->save('php://output'),
            'recaudos-cartera-'.$desde.'-a-'.$hasta.'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    /**
     * Cuentas de caja por islas y cartera, y la cuenta contable de cada banco.
     */
    public function guardarCuentas(Request $request): RedirectResponse
    {
        $request->validate([
            'cuentas' => ['array'],
            'cuentas.*.cuenta' => ['required', 'string', 'max:30'],
            'bancos' => ['array'],
            'bancos.*' => ['nullable', 'string', 'max:30'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ]);

        DB::transaction(function () use ($request): void {
            foreach ($request->input('cuentas', []) as $clave => $datos) {
                ComprobanteRecaudoCuenta::query()
                    ->where('clave', (string) $clave)
                    ->update(['cuenta' => trim((string) $datos['cuenta'])]);
            }

            foreach ($request->input('bancos', []) as $id => $cuenta) {
                Banco::query()->whereKey((int) $id)->update(['cuenta_contable' => trim((string) $cuenta) ?: null]);
            }
        });

        return redirect()->route('informes.recaudos-cartera.index', $request->only('desde', 'hasta'))
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
