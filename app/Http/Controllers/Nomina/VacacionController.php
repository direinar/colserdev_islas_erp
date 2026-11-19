<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Models\Nomina\Empleado;
use App\Models\Nomina\Vacacion;
use App\Services\Nomina\PrestacionesSocialesService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Formato de control de vacaciones por empleado: causadas, tomadas y saldo con semáforo.
 */
class VacacionController extends Controller
{
    public function __construct(private PrestacionesSocialesService $prestaciones) {}

    public function index(): View
    {
        $empleados = Empleado::query()->activos()->with('vacaciones')->orderBy('nombres')->get();

        return view('nomina.vacaciones.index', [
            'filas' => $empleados->map(fn (Empleado $empleado): array => ['empleado' => $empleado] + $this->prestaciones->controlVacaciones($empleado)),
        ]);
    }

    public function show(Empleado $empleado): View
    {
        $empleado->load(['vacaciones' => fn ($query) => $query->orderByDesc('fecha_inicio')]);

        return view('nomina.vacaciones.show', [
            'empleado' => $empleado,
            'control' => $this->prestaciones->controlVacaciones($empleado),
        ]);
    }

    public function store(Request $request, Empleado $empleado): RedirectResponse
    {
        $validated = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'dias_disfrutados' => ['nullable', 'integer', 'min:0', 'max:90'],
            'dias_dinero' => ['nullable', 'integer', 'min:0', 'max:90'],
            'periodo' => ['nullable', 'string', 'max:40'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        if (((int) ($validated['dias_disfrutados'] ?? 0)) + ((int) ($validated['dias_dinero'] ?? 0)) === 0) {
            return back()->withInput()->withErrors(['dias_disfrutados' => 'Indique los días disfrutados o pagados en dinero.']);
        }

        $empleado->vacaciones()->create([
            ...$validated,
            'dias_disfrutados' => (int) ($validated['dias_disfrutados'] ?? 0),
            'dias_dinero' => (int) ($validated['dias_dinero'] ?? 0),
        ]);

        return redirect()->route('nomina.vacaciones.show', $empleado)->with('success', 'Vacaciones registradas.');
    }

    public function destroy(Empleado $empleado, Vacacion $vacacion): RedirectResponse
    {
        abort_unless($vacacion->empleado_id === $empleado->id, 404);
        $vacacion->delete();

        return redirect()->route('nomina.vacaciones.show', $empleado)->with('success', 'Registro de vacaciones eliminado.');
    }
}
