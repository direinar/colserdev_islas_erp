<?php

namespace App\Http\Controllers;

use App\Concerns\ResuelveRangoFechas;
use App\Models\Banco;
use App\Models\ComprobanteRecaudoCuenta;
use App\Services\ComprobanteRecaudosExcelService;
use App\Services\RecaudosCarteraService;
use App\Support\DescargaExcel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Comprobante contable de recaudos de cartera (antes, la tabla de la derecha del informe
 * de recaudos de cartera) y sus cuentas contables.
 */
class InformeComprobanteRecaudosController extends Controller
{
    use ResuelveRangoFechas;

    public function index(Request $request, RecaudosCarteraService $recaudos): View
    {
        [$desde, $hasta] = $this->rango($request);

        return view('informes.comprobante-recaudos.index', [
            'desde' => $desde,
            'hasta' => $hasta,
        ] + $recaudos->comprobanteRecaudos($desde, $hasta));
    }

    public function exportar(Request $request, ComprobanteRecaudosExcelService $excel): StreamedResponse
    {
        [$desde, $hasta] = $this->rango($request);

        $libro = $excel->generar($desde, $hasta);

        return DescargaExcel::respuesta($libro, 'comprobante-recaudos-cartera-'.$desde.'-a-'.$hasta.'.xlsx');
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

        return redirect()->route('informes.comprobante-recaudos.index', $request->only('desde', 'hasta'))
            ->with('success', 'Cuentas contables guardadas correctamente.');
    }
}
