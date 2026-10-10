<?php

namespace App\Http\Controllers;

use App\Models\LiquidacionAnticipoBimestral;
use App\Models\MargenIntermediacion;
use App\Services\AnticipoBimestralExcelService;
use App\Services\AnticipoBimestralService;
use App\Support\DescargaExcel;
use App\Support\NumberParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InformeAnticipoBimestralController extends Controller
{
    public function index(Request $request, AnticipoBimestralService $anticipo): View
    {
        [$anio, $bimestre] = $this->periodo($request);

        return view('informes.anticipo-bimestral.index', $anticipo->informe($anio, $bimestre));
    }

    public function exportar(Request $request, AnticipoBimestralExcelService $excel): StreamedResponse
    {
        [$anio, $bimestre] = $this->periodo($request);

        $libro = $excel->generar($anio, $bimestre);

        return DescargaExcel::respuesta($libro, 'anticipo-bimestral-'.$anio.'-bimestre-'.$bimestre.'.xlsx');
    }

    public function guardarAjustes(Request $request): RedirectResponse
    {
        $request->validate([
            'anio' => ['required', 'integer', 'min:2000', 'max:2100'],
            'bimestre' => ['required', 'integer', 'min:1', 'max:6'],
            'otros_ingresos' => ['nullable', 'string', 'max:30'],
            'descuentos_ventas' => ['nullable', 'string', 'max:30'],
        ]);

        LiquidacionAnticipoBimestral::query()->updateOrCreate(
            ['anio' => (int) $request->input('anio'), 'bimestre' => (int) $request->input('bimestre')],
            [
                'otros_ingresos' => NumberParser::money($request->input('otros_ingresos')),
                'descuentos_ventas' => NumberParser::money($request->input('descuentos_ventas')),
            ],
        );

        return redirect()->route('informes.anticipo-bimestral.index', $request->only('anio', 'bimestre'))
            ->with('success', 'Liquidación del anticipo guardada correctamente.');
    }

    /**
     * Registra un margen de intermediación desde una fecha; si ya hay uno con esa fecha, lo corrige.
     */
    public function guardarMargen(Request $request): RedirectResponse
    {
        $request->validate([
            'vigente_desde' => ['required', 'date'],
            'valor' => ['required', 'string', 'max:20'],
            'anio' => ['nullable', 'integer'],
            'bimestre' => ['nullable', 'integer'],
        ]);

        $valor = NumberParser::quantity($request->input('valor'));

        if ($valor <= 0) {
            return back()->withErrors(['valor' => 'El margen de intermediación debe ser mayor que cero.']);
        }

        $margen = MargenIntermediacion::query()->whereDate('vigente_desde', $request->input('vigente_desde'))->first()
            ?? new MargenIntermediacion(['vigente_desde' => $request->input('vigente_desde')]);
        $margen->fill(['valor' => $valor])->save();

        return redirect()->route('informes.anticipo-bimestral.index', $request->only('anio', 'bimestre'))
            ->with('success', 'Margen de intermediación guardado correctamente.');
    }

    /**
     * Por defecto, el bimestre en curso.
     *
     * @return array{0: int, 1: int}
     */
    private function periodo(Request $request): array
    {
        $request->validate([
            'anio' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'bimestre' => ['nullable', 'integer', 'min:1', 'max:6'],
        ]);

        return [
            (int) ($request->query('anio') ?: now()->year),
            (int) ($request->query('bimestre') ?: intdiv(now()->month + 1, 2)),
        ];
    }
}
