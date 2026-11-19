<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Models\Nomina\PlanillaTurno;
use App\Models\Nomina\Turno;
use App\Services\Nomina\PlanillaTurnosService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlanillaTurnoController extends Controller
{
    public function __construct(private PlanillaTurnosService $planillas) {}

    public function index(): View
    {
        $planillas = PlanillaTurno::withCount('asignaciones')
            ->orderByDesc('anio')
            ->orderByDesc('mes')
            ->get();

        $siguiente = $planillas->first()
            ? $planillas->first()->inicioDelMes()->addMonth()
            : now()->startOfMonth();

        return view('nomina.planillas-turnos.index', compact('planillas', 'siguiente'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'anio' => ['required', 'integer', 'min:2020', 'max:2100'],
            'mes' => [
                'required', 'integer', 'min:1', 'max:12',
                Rule::unique('nomina_planillas_turnos', 'mes')->where('anio', $request->integer('anio')),
            ],
        ], [
            'mes.unique' => 'Ya existe una planilla para ese mes.',
        ]);

        $planilla = PlanillaTurno::create($validated);

        return redirect()
            ->route('nomina.planillas-turnos.edit', $planilla)
            ->with('success', 'Planilla creada: asigne los turnos de cada empleado.');
    }

    /**
     * Versión 1: solo los turnos, para publicar los horarios a los isleros.
     */
    public function show(PlanillaTurno $planillas_turno): View
    {
        return view('nomina.planillas-turnos.show', [
            'planilla' => $planillas_turno,
            'semanas' => $this->planillas->semanas($planillas_turno),
            'empleados' => $this->planillas->empleados($planillas_turno),
            'resumen' => $this->planillas->resumen($planillas_turno),
            'festivos' => $this->planillas->festivosDelPeriodo($planillas_turno),
        ]);
    }

    public function edit(PlanillaTurno $planillas_turno): View|RedirectResponse
    {
        if ($planillas_turno->estaCerrada()) {
            return redirect()
                ->route('nomina.planillas-turnos.show', $planillas_turno)
                ->with('error', 'La planilla está cerrada: reábrala para modificar los turnos.');
        }

        $resumen = $this->planillas->resumen($planillas_turno);
        $asignados = collect($resumen)->flatMap(fn (array $fila) => collect($fila['dias'])->pluck('turno_id'))->unique();

        return view('nomina.planillas-turnos.edit', [
            'planilla' => $planillas_turno,
            'semanas' => $this->planillas->semanas($planillas_turno),
            'empleados' => $this->planillas->empleados($planillas_turno),
            'resumen' => $resumen,
            'festivos' => $this->planillas->festivosDelPeriodo($planillas_turno),
            'turnos' => Turno::query()
                ->where('activo', true)
                ->orWhereIn('id', $asignados)
                ->orderBy('hora_inicio')
                ->get(),
        ]);
    }

    public function update(Request $request, PlanillaTurno $planillas_turno): RedirectResponse
    {
        if ($planillas_turno->estaCerrada()) {
            return redirect()
                ->route('nomina.planillas-turnos.show', $planillas_turno)
                ->with('error', 'La planilla está cerrada: reábrala para modificar los turnos.');
        }

        $validated = $request->validate([
            'turnos' => ['nullable', 'array'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->planillas->guardar($planillas_turno, $validated['turnos'] ?? []);
        $planillas_turno->update(['observaciones' => $validated['observaciones'] ?? null]);

        return redirect()
            ->route('nomina.planillas-turnos.edit', $planillas_turno)
            ->with('success', 'Turnos guardados y horas recalculadas.');
    }

    /**
     * Versión 2 (fin de mes): recargos nocturnos y festivos, horas por semana y resumen del mes.
     */
    public function liquidacion(PlanillaTurno $planillas_turno): View
    {
        return view('nomina.planillas-turnos.liquidacion', [
            'planilla' => $planillas_turno,
            'semanas' => $this->planillas->semanas($planillas_turno),
            'empleados' => $this->planillas->empleados($planillas_turno),
            'resumen' => $this->planillas->resumen($planillas_turno),
            'jornadas' => $this->planillas->jornadaPorSemana($planillas_turno),
            'festivos' => $this->planillas->festivosDelPeriodo($planillas_turno),
        ]);
    }

    /**
     * Cerrar recalcula las horas con los parámetros y festivos actuales y bloquea la edición.
     */
    public function cerrar(PlanillaTurno $planillas_turno): RedirectResponse
    {
        $this->planillas->recalcular($planillas_turno);
        $planillas_turno->update(['estado' => PlanillaTurno::ESTADO_CERRADA]);

        return redirect()
            ->route('nomina.planillas-turnos.liquidacion', $planillas_turno)
            ->with('success', 'Planilla cerrada: los recargos quedan listos para la nómina.');
    }

    public function reabrir(PlanillaTurno $planillas_turno): RedirectResponse
    {
        $planillas_turno->update(['estado' => PlanillaTurno::ESTADO_BORRADOR]);

        return redirect()
            ->route('nomina.planillas-turnos.edit', $planillas_turno)
            ->with('success', 'Planilla reabierta para edición.');
    }

    public function destroy(PlanillaTurno $planillas_turno): RedirectResponse
    {
        if ($planillas_turno->estaCerrada()) {
            return redirect()
                ->route('nomina.planillas-turnos.index')
                ->with('error', 'No se puede eliminar una planilla cerrada.');
        }

        $planillas_turno->delete();

        return redirect()
            ->route('nomina.planillas-turnos.index')
            ->with('success', 'Planilla eliminada correctamente.');
    }
}
