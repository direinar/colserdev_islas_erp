<?php

namespace App\Http\Controllers;

use App\Services\InformeGobernacionSicomExcelService;
use App\Services\InformeGobernacionSicomService;
use App\Support\DescargaExcel;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Informe mensual para Gobernación y SICOM: lecturas electrónicas, compras y resumen de
 * galones para la declaración.
 */
class InformeGobernacionSicomController extends Controller
{
    public function index(Request $request, InformeGobernacionSicomService $informe): View
    {
        return view('informes.gobernacion-sicom.index', $informe->informe($this->mes($request)));
    }

    public function exportar(Request $request, InformeGobernacionSicomExcelService $excel): StreamedResponse
    {
        $mes = $this->mes($request);

        return DescargaExcel::respuesta($excel->generar($mes), 'informe-gobernacion-sicom-'.$mes.'.xlsx');
    }

    /**
     * Mes del informe (Y-m). Por defecto el mes anterior: el que se declara.
     */
    private function mes(Request $request): string
    {
        $request->validate(['mes' => ['nullable', 'date_format:Y-m']]);

        return $request->query('mes') ?: now()->subMonthNoOverflow()->format('Y-m');
    }
}
