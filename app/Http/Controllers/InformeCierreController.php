<?php

namespace App\Http\Controllers;

use App\Concerns\ResuelveRangoFechas;
use App\Models\GalonajeContrato;
use App\Services\InformeCierreExcelService;
use App\Services\InformeCierreService;
use App\Support\DescargaExcel;
use App\Support\NumberParser;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InformeCierreController extends Controller
{
    use ResuelveRangoFechas;

    public function index(Request $request, InformeCierreService $cierre): View
    {
        [$desde, $hasta] = $this->rango($request);

        return view('informes.cierre.index', [
            'desde' => $desde,
            'hasta' => $hasta,
        ] + $cierre->informe($desde, $hasta));
    }

    public function exportar(Request $request, InformeCierreExcelService $excel): StreamedResponse
    {
        [$desde, $hasta] = $this->rango($request);

        $libro = $excel->generar($desde, $hasta);

        return DescargaExcel::respuesta($libro, 'informes-de-gestion-'.$desde.'-a-'.$hasta.'.xlsx');
    }

    /**
     * Informes de gestión en PDF, para descargarlo o compartirlo por WhatsApp desde el módulo.
     */
    public function pdf(Request $request, InformeCierreService $cierre, InformeCierreExcelService $excel): Response
    {
        [$desde, $hasta] = $this->rango($request);
        $informe = $cierre->informe($desde, $hasta);

        return Pdf::loadView('informes.cierre.pdf', [
            'desde' => $desde,
            'hasta' => $hasta,
            'renglones' => $excel->renglones($informe['valores']),
            'estadisticas' => $excel->estadisticas($informe['estadisticas']),
        ])->download('informes-de-gestion-'.$desde.'-a-'.$hasta.'.pdf');
    }

    /**
     * Saldos iniciales del contrato de galonaje a una fecha de corte. Repetir la fecha los corrige;
     * una fecha nueva reinicia el conteo desde ahí.
     */
    public function guardarContrato(Request $request): RedirectResponse
    {
        $request->validate([
            'fecha_corte' => ['required', 'date'],
            'galonaje_acumulado_inicial' => ['required', 'string', 'max:30'],
            'saldo_contrato_inicial' => ['required', 'string', 'max:30'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ]);

        $contrato = GalonajeContrato::query()->whereDate('fecha_corte', $request->input('fecha_corte'))->first()
            ?? new GalonajeContrato(['fecha_corte' => $request->input('fecha_corte')]);
        $contrato->fill([
            'galonaje_acumulado_inicial' => NumberParser::quantity($request->input('galonaje_acumulado_inicial')),
            'saldo_contrato_inicial' => NumberParser::quantity($request->input('saldo_contrato_inicial')),
        ])->save();

        return redirect()->route('informes.cierre.index', $request->only('desde', 'hasta'))
            ->with('success', 'Saldos del contrato de galonaje guardados correctamente.');
    }
}
