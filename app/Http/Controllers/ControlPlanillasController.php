<?php

namespace App\Http\Controllers;

use App\Concerns\ResuelveRangoFechas;
use App\Models\TurnoAuditoria;
use App\Services\AuditoriaPlanillaService;
use App\Services\InformeAuditoriaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Auditoría del administrador (informe de auditoría del periodo): alertas de guardados que
 * quitaron datos, estado de revisión, planillas incompletas, números de turno faltantes,
 * lectura electrónica contra IAPROPIADA, informe de tanques contra inventario, últimas
 * planillas y comprobantes contables descuadrados.
 */
class ControlPlanillasController extends Controller
{
    use ResuelveRangoFechas;

    public function index(Request $request, AuditoriaPlanillaService $auditoria, InformeAuditoriaService $informe): View
    {
        [$desde, $hasta] = $this->rango($request);

        return view('control-planillas.index', [
            'desde' => $desde,
            'hasta' => $hasta,
            'alertas' => TurnoAuditoria::query()->pendientes()->with(['user', 'turno'])->latest()->get(),
            'atendidas' => TurnoAuditoria::query()->whereNotNull('alerta')->whereNotNull('atendida_at')
                ->with(['user', 'turno'])->latest('atendida_at')->limit(30)->get(),
            'revision' => $auditoria->estadoRevision($desde, $hasta),
            'incompletas' => $auditoria->incompletas($desde, $hasta),
            'faltantes' => $auditoria->numerosFaltantes(),
            'lecturas' => $informe->lecturas($desde, $hasta),
            'inventarios' => $informe->inventarios($desde, $hasta),
            'ultimas' => $informe->ultimasPlanillas($desde, $hasta),
            'comprobantes' => $informe->comprobantes($desde, $hasta),
        ]);
    }

    /**
     * Historial de guardados de una planilla: quién la guardó y cuántas filas tenía cada sección.
     */
    public function historial(int $numero): View
    {
        return view('control-planillas.historial', [
            'numero' => $numero,
            'registros' => TurnoAuditoria::query()->where('numero_turno', $numero)->with('user')->oldest()->get(),
        ]);
    }

    public function atender(Request $request, TurnoAuditoria $auditoria): RedirectResponse
    {
        $auditoria->update(['atendida_at' => now(), 'atendida_por' => $request->user()->name]);

        return redirect()->route('control-planillas.index')->with('success', 'Alerta del turno #'.$auditoria->numero_turno.' marcada como revisada.');
    }
}
