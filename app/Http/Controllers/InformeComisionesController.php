<?php

namespace App\Http\Controllers;

use App\Concerns\ResuelveRangoFechas;
use App\Models\ComisionPorcentaje;
use App\Services\ComisionesIslerosService;
use App\Support\DescargaExcel;
use App\Support\NumberParser;
use App\Support\TablaExcel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Informe de ventas de combustible y canastilla por islero, con su comisión.
 */
class InformeComisionesController extends Controller
{
    use ResuelveRangoFechas;

    public function index(Request $request, ComisionesIslerosService $comisiones): View
    {
        [$desde, $hasta] = $this->rango($request);

        return view('informes.comisiones.index', [
            'desde' => $desde,
            'hasta' => $hasta,
        ] + $comisiones->informe($desde, $hasta));
    }

    public function exportar(Request $request, ComisionesIslerosService $comisiones): StreamedResponse
    {
        [$desde, $hasta] = $this->rango($request);
        $informe = $comisiones->informe($desde, $hasta);
        $porcentajes = $informe['porcentajes'];

        $libro = TablaExcel::generar(
            'Comisiones de isleros',
            'Desde '.Carbon::parse($desde)->format('d/m/Y').' hasta '.Carbon::parse($hasta)->format('d/m/Y')
                .' — combustible '.$this->porcentaje($porcentajes->combustible).'%, canastilla '.$this->porcentaje($porcentajes->canastilla).'%',
            ['ISLERO', 'PLANILLAS', 'GALONES', 'VENTA COMBUSTIBLE', 'COMISIÓN COMBUSTIBLE', 'VENTA CANASTILLA', 'COMISIÓN CANASTILLA', 'TOTAL COMISIÓN'],
            array_map(fn (array $fila): array => array_values($fila), $informe['filas']),
            [0],
            ['TOTALES', ...array_values($informe['totales'])],
        );

        return DescargaExcel::respuesta($libro, 'comisiones-isleros-'.$desde.'-a-'.$hasta.'.xlsx');
    }

    /**
     * Porcentajes de comisión, iguales para todos los isleros.
     */
    public function guardarPorcentajes(Request $request): RedirectResponse
    {
        $request->validate([
            'combustible' => ['required', 'string', 'max:10'],
            'canastilla' => ['required', 'string', 'max:10'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ]);

        $combustible = NumberParser::decimal($request->input('combustible'));
        $canastilla = NumberParser::decimal($request->input('canastilla'));

        if ($combustible < 0 || $combustible > 100 || $canastilla < 0 || $canastilla > 100) {
            return back()->withErrors(['porcentajes' => 'Los porcentajes deben estar entre 0 y 100.'])->withInput();
        }

        $porcentajes = ComisionPorcentaje::query()->first() ?? new ComisionPorcentaje;
        $porcentajes->fill(['combustible' => $combustible, 'canastilla' => $canastilla])->save();

        return redirect()->route('informes.comisiones.index', $request->only('desde', 'hasta'))
            ->with('success', 'Porcentajes de comisión guardados.');
    }

    private function porcentaje(mixed $valor): string
    {
        return rtrim(rtrim(number_format((float) $valor, 3, ',', '.'), '0'), ',');
    }
}
