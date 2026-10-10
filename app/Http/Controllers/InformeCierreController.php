<?php

namespace App\Http\Controllers;

use App\Models\GalonajeContrato;
use App\Services\InformeCierreExcelService;
use App\Services\InformeCierreService;
use App\Support\NumberParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InformeCierreController extends Controller
{
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

        return response()->streamDownload(
            fn () => (new Xlsx($libro))->save('php://output'),
            'informe-cierre-'.$desde.'-a-'.$hasta.'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
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
