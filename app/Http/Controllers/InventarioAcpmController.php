<?php

namespace App\Http\Controllers;

use App\Concerns\ResuelveRangoFechas;
use App\Services\KardexExcelService;
use App\Services\KardexInventarioService;
use App\Support\DescargaExcel;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventarioAcpmController extends Controller
{
    use ResuelveRangoFechas;

    public function create(Request $request, KardexInventarioService $kardexService): View
    {
        [$desde, $hasta] = $this->rango($request);

        return view('inventarios.acpm.create', [
            'kardex' => $kardexService->delPeriodo($kardexService->acpm(), $desde, $hasta),
            'desde' => $desde,
            'hasta' => $hasta,
        ]);
    }

    public function exportar(Request $request, KardexInventarioService $kardexService, KardexExcelService $excel): StreamedResponse
    {
        [$desde, $hasta] = $this->rango($request);
        $kardex = $kardexService->delPeriodo($kardexService->acpm(), $desde, $hasta);

        return DescargaExcel::respuesta(
            $excel->generar('INVENTARIOS ACPM', 'GALONES', $kardex, $desde, $hasta),
            'inventario-acpm-'.$desde.'-a-'.$hasta.'.xlsx',
        );
    }
}
