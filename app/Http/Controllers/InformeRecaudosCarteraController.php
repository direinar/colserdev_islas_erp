<?php

namespace App\Http\Controllers;

use App\Concerns\ResuelveRangoFechas;
use App\Services\RecaudosCarteraExcelService;
use App\Services\RecaudosCarteraService;
use App\Support\DescargaExcel;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InformeRecaudosCarteraController extends Controller
{
    use ResuelveRangoFechas;

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

        return DescargaExcel::respuesta($libro, 'recaudos-cartera-'.$desde.'-a-'.$hasta.'.xlsx');
    }
}
