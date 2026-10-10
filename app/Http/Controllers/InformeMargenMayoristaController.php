<?php

namespace App\Http\Controllers;

use App\Concerns\ResuelveRangoFechas;
use App\Models\MargenMayorista;
use App\Services\MargenMayoristaExcelService;
use App\Services\MargenMayoristaService;
use App\Support\DescargaExcel;
use App\Support\NumberParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InformeMargenMayoristaController extends Controller
{
    use ResuelveRangoFechas;

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

        return DescargaExcel::respuesta($libro, 'margen-mayorista-'.$desde.'-a-'.$hasta.'.xlsx');
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
}
