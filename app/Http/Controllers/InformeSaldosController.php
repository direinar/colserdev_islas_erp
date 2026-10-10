<?php

namespace App\Http\Controllers;

use App\Services\InformeSaldosExcelService;
use App\Services\InformeSaldosService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InformeSaldosController extends Controller
{
    public const SECCIONES = ['cartera', 'canastilla', 'precios'];

    public function index(Request $request, InformeSaldosService $saldos): View
    {
        [$seccion, $corte, $soloConSaldo] = $this->filtros($request);

        return view('informes.saldos.index', [
            'seccion' => $seccion,
            'corte' => $corte,
            'soloConSaldo' => $soloConSaldo,
            'datos' => match ($seccion) {
                'cartera' => $saldos->cartera($corte, $soloConSaldo),
                'canastilla' => $saldos->canastilla($corte),
                'precios' => $saldos->precios(),
            },
        ]);
    }

    public function exportar(Request $request, InformeSaldosExcelService $excel): StreamedResponse
    {
        [$seccion, $corte, $soloConSaldo] = $this->filtros($request);

        $libro = $excel->generar($seccion, $corte, $soloConSaldo);

        return response()->streamDownload(
            fn () => (new Xlsx($libro))->save('php://output'),
            'saldos-'.$seccion.'-'.$corte.'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    /**
     * Sección (por defecto cartera), fecha de corte (por defecto hoy) y si solo se muestran
     * los clientes con saldo (por defecto sí).
     *
     * @return array{0: string, 1: string, 2: bool}
     */
    private function filtros(Request $request): array
    {
        $request->validate([
            'seccion' => ['nullable', Rule::in(self::SECCIONES)],
            'corte' => ['nullable', 'date'],
            'todos' => ['nullable', 'boolean'],
        ]);

        return [
            $request->query('seccion') ?: 'cartera',
            $request->date('corte')?->format('Y-m-d') ?? now()->format('Y-m-d'),
            ! $request->boolean('todos'),
        ];
    }
}
