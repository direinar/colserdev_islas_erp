<?php

namespace App\Http\Controllers;

use App\Concerns\ResuelveRangoFechas;
use App\Services\ComprobanteConteoExcelService;
use App\Services\ComprobanteConteoService;
use App\Support\DescargaExcel;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Conteo → Comprobante contable: comprobantes de los conteos del periodo (DESDE / HASTA),
 * para imprimir o descargar en Excel.
 */
class ComprobanteConteoController extends Controller
{
    use ResuelveRangoFechas;

    public function index(Request $request, ComprobanteConteoService $comprobantes): View
    {
        [$desde, $hasta] = $this->rango($request);

        return view('conteos.comprobante', [
            'desde' => $desde,
            'hasta' => $hasta,
            'periodo' => $comprobantes->delPeriodo($desde, $hasta),
        ]);
    }

    public function exportar(Request $request, ComprobanteConteoExcelService $excel): StreamedResponse
    {
        [$desde, $hasta] = $this->rango($request);

        return DescargaExcel::respuesta($excel->generar($desde, $hasta), 'comprobante-conteo-'.$desde.'-a-'.$hasta.'.xlsx');
    }
}
