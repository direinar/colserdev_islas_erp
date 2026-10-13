<?php

namespace App\Http\Controllers;

use App\Concerns\ResuelveRangoFechas;
use App\Services\ControlCajaExcelService;
use App\Services\ControlCajaService;
use App\Support\DescargaExcel;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InformeControlCajaController extends Controller
{
    use ResuelveRangoFechas;

    public function index(Request $request, ControlCajaService $controlCaja): View
    {
        [$desde, $hasta] = $this->rango($request);

        return view('informes.control-caja.index', [
            'desde' => $desde,
            'hasta' => $hasta,
        ] + $controlCaja->informe($desde, $hasta));
    }

    public function exportar(Request $request, ControlCajaExcelService $excel): StreamedResponse
    {
        [$desde, $hasta] = $this->rango($request);

        $libro = $excel->generar($desde, $hasta);

        return DescargaExcel::respuesta($libro, 'control-caja-'.$desde.'-a-'.$hasta.'.xlsx');
    }
}
