<?php

namespace App\Http\Controllers;

use App\Models\MargenMayorista;
use App\Services\MargenMayoristaExcelService;
use App\Services\MargenMayoristaService;
use App\Support\NumberParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InformeMargenMayoristaController extends Controller
{
    public function index(Request $request, MargenMayoristaService $margenMayorista): View
    {
        [$desde, $hasta] = $this->rango($request);

        return view('informes.margen-mayorista.index', [
            'desde' => $desde,
            'hasta' => $hasta,
        ] + $margenMayorista->informe($desde, $hasta));
    }

    public function exportar(Request $request, MargenMayoristaExcelService $excel): StreamedResponse
    {
        [$desde, $hasta] = $this->rango($request);

        $libro = $excel->generar($desde, $hasta);

        return response()->streamDownload(
            fn () => (new Xlsx($libro))->save('php://output'),
            'margen-mayorista-'.$desde.'-a-'.$hasta.'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    /**
     * Registra los parámetros del convenio desde una fecha; si ya hay unos con esa fecha, los corrige.
     */
    public function guardarParametros(Request $request): RedirectResponse
    {
        $request->validate([
            'vigente_desde' => ['required', 'date'],
            'porcentaje' => ['required', 'string', 'max:20'],
            'margen' => ['required', 'string', 'max:20'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ]);

        $porcentaje = NumberParser::quantity($request->input('porcentaje'));
        $margen = NumberParser::quantity($request->input('margen'));

        if ($porcentaje <= 0 || $porcentaje > 1 || $margen <= 0) {
            return back()->withErrors(['porcentaje' => 'El porcentaje debe estar entre 0 y 1 (p. ej. 0,3) y el margen debe ser mayor que cero.']);
        }

        $parametro = MargenMayorista::query()->whereDate('vigente_desde', $request->input('vigente_desde'))->first()
            ?? new MargenMayorista(['vigente_desde' => $request->input('vigente_desde')]);
        $parametro->fill(['porcentaje' => $porcentaje, 'margen' => $margen])->save();

        return redirect()->route('informes.margen-mayorista.index', $request->only('desde', 'hasta'))
            ->with('success', 'Parámetros del convenio de mayorista guardados correctamente.');
    }

    /**
     * Por defecto, el mes en curso hasta hoy (el margen mayorista se liquida mensualmente).
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
