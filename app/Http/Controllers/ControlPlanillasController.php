<?php

namespace App\Http\Controllers;

use App\Models\TurnoAuditoria;
use App\Services\AuditoriaPlanillaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Control de planillas borradas o incompletas (administración y gerencia): alertas de guardados
 * que quitaron datos, planillas sin ventas o sin dinero recibido y números de turno faltantes.
 */
class ControlPlanillasController extends Controller
{
    public function index(AuditoriaPlanillaService $auditoria): View
    {
        return view('control-planillas.index', [
            'alertas' => TurnoAuditoria::query()->pendientes()->with(['user', 'turno'])->latest()->get(),
            'atendidas' => TurnoAuditoria::query()->whereNotNull('alerta')->whereNotNull('atendida_at')
                ->with(['user', 'turno'])->latest('atendida_at')->limit(30)->get(),
            'incompletas' => $auditoria->incompletas(),
            'faltantes' => $auditoria->numerosFaltantes(),
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
