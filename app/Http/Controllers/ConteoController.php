<?php

namespace App\Http\Controllers;

use App\Models\Conteo;
use App\Models\ConteoDestino;
use App\Models\ConteoTercero;
use App\Services\ConteoExcelService;
use App\Services\ConteoService;
use App\Support\DescargaExcel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Módulo CONTEO: cuadre del efectivo de la EDS. Cada tabla de la plantilla es un submódulo
 * (Conteo::SECCIONES) que se guarda por separado sobre el mismo conteo.
 */
class ConteoController extends Controller
{
    public function index(): View
    {
        return view('conteos.index', [
            'conteos' => Conteo::query()->with('user')->orderByDesc('numero')->paginate(20),
            'esPrimero' => ! Conteo::query()->exists(),
        ]);
    }

    public function store(Request $request, ConteoService $conteos): RedirectResponse
    {
        $esPrimero = ! Conteo::query()->exists();

        $validated = $request->validate([
            'fecha' => ['required', 'date'],
            // El primer conteo trae el punto de partida (como el saldo inicial de cartera).
            'consignacion_anterior' => [$esPrimero ? 'required' : 'nullable', 'integer', 'min:0'],
            'saldo_moneda_inicial' => [$esPrimero ? 'required' : 'nullable', 'string', 'max:30'],
        ], [
            'consignacion_anterior.required' => 'Digite el último No. de consignación del conteo anterior (punto de partida).',
            'saldo_moneda_inicial.required' => 'Digite el saldo de moneda con el que arranca el control de moneda.',
        ]);

        $conteo = $conteos->crear($validated, $request->user());

        return redirect()->route('conteos.seccion', [$conteo, 'efectivo'])
            ->with('success', 'Conteo No. '.$conteo->numero.' creado. Digite el cuadre de efectivo.');
    }

    public function seccion(Conteo $conteo, string $seccion, ConteoService $conteos): View
    {
        abort_unless(array_key_exists($seccion, Conteo::SECCIONES), 404);

        $conteo->load(['billetes', 'movimientos', 'consignaciones.turno', 'aplicaciones']);

        return view('conteos.secciones.'.$seccion, [
            'conteo' => $conteo,
            'seccion' => $seccion,
            'resumen' => $conteos->resumen($conteo),
            'editable' => $conteo->esUltimo(),
            'listaConteos' => Conteo::query()->orderByDesc('numero')->get(['id', 'numero', 'fecha']),
            'destinos' => ConteoDestino::query()->orderBy('nombre')->get(),
            'terceros' => ConteoTercero::query()->orderBy('nombre')->get(),
        ]);
    }

    public function guardar(Request $request, Conteo $conteo, string $seccion, ConteoService $conteos): RedirectResponse
    {
        abort_unless(array_key_exists($seccion, Conteo::SECCIONES), 404);

        $fila = ['nullable', 'string', 'max:30'];
        $reglas = match ($seccion) {
            'efectivo' => [
                'billetes' => ['nullable', 'array'],
                'billetes.*.denominacion' => ['nullable', Rule::in(Conteo::DENOMINACIONES)],
                'billetes.*.valor' => $fila,
                'moneda' => $fila,
            ],
            'consignaciones' => [
                'ultima_consignacion' => ['required', 'integer', 'min:1'],
                'consignacion_anterior' => ['nullable', 'integer', 'min:0'],
            ],
            'aplicaciones' => [
                'filas' => ['nullable', 'array'],
                'filas.*.conteo_destino_id' => ['nullable', 'integer', 'exists:conteo_destinos,id'],
                'filas.*.conteo_tercero_id' => ['nullable', 'integer', 'exists:conteo_terceros,id'],
                'filas.*.valor' => $fila,
            ],
            default => [
                'filas' => ['nullable', 'array'],
                'filas.*.nombre' => ['nullable', 'string', 'max:120'],
                'filas.*.concepto' => ['nullable', 'string', 'max:200'],
                'filas.*.valor' => $fila,
                'saldo_moneda_inicial' => $fila,
            ],
        };

        $conteos->guardar($conteo, $seccion, $request->validate($reglas, [
            'ultima_consignacion.required' => 'Digite el último No. de consignación de este conteo.',
        ]));

        return redirect()->route('conteos.seccion', [$conteo, $seccion])
            ->with('success', Conteo::SECCIONES[$seccion].' del conteo No. '.$conteo->numero.' guardado.');
    }

    /**
     * El conteo completo, con todas sus tablas, para imprimir.
     */
    public function reporte(Conteo $conteo, ConteoService $conteos): View
    {
        return view('conteos.reporte', $this->datosReporte($conteo, $conteos) + ['paraPdf' => false]);
    }

    public function pdf(Conteo $conteo, ConteoService $conteos): Response
    {
        return Pdf::loadView('conteos.reporte', $this->datosReporte($conteo, $conteos) + ['paraPdf' => true])
            ->setPaper('letter')
            ->download('conteo-'.$conteo->numero.'-'.$conteo->fecha->format('Y-m-d').'.pdf');
    }

    public function exportar(Conteo $conteo, ConteoExcelService $excel): StreamedResponse
    {
        return DescargaExcel::respuesta($excel->generar($conteo), 'conteo-'.$conteo->numero.'-'.$conteo->fecha->format('Y-m-d').'.xlsx');
    }

    /**
     * @return array<string, mixed>
     */
    private function datosReporte(Conteo $conteo, ConteoService $conteos): array
    {
        $conteo->load(['billetes', 'movimientos', 'consignaciones.turno', 'aplicaciones.destino', 'aplicaciones.tercero', 'user']);

        return ['conteo' => $conteo, 'resumen' => $conteos->resumen($conteo)];
    }
}
