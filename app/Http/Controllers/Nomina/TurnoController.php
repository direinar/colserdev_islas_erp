<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Models\Nomina\AsignacionTurno;
use App\Models\Nomina\ParametroLiquidacion;
use App\Models\Nomina\Turno;
use App\Services\Nomina\CalculoHorasTurnoService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TurnoController extends Controller
{
    public function index(CalculoHorasTurnoService $calculo): View
    {
        $turnos = Turno::orderBy('hora_inicio')->get();
        $parametros = ParametroLiquidacion::vigenteEn(now());

        $horasNocturnas = $parametros
            ? $turnos->mapWithKeys(fn (Turno $turno) => [$turno->id => $calculo->horasNocturnas($turno, $parametros)])
            : collect();

        return view('nomina.turnos.index', compact('turnos', 'parametros', 'horasNocturnas'));
    }

    public function create(): View
    {
        return view('nomina.turnos.create');
    }

    public function store(Request $request): RedirectResponse
    {
        Turno::create($this->validar($request));

        return redirect()
            ->route('nomina.turnos.index')
            ->with('success', 'Turno creado correctamente.');
    }

    public function edit(Turno $turno): View
    {
        return view('nomina.turnos.edit', compact('turno'));
    }

    public function update(Request $request, Turno $turno): RedirectResponse
    {
        $turno->update($this->validar($request, $turno));

        return redirect()
            ->route('nomina.turnos.index')
            ->with('success', 'Turno actualizado correctamente.');
    }

    public function destroy(Turno $turno): RedirectResponse
    {
        if (AsignacionTurno::where('turno_id', $turno->id)->exists()) {
            return redirect()
                ->route('nomina.turnos.index')
                ->with('error', 'El turno ya se usó en planillas: desactívelo en lugar de eliminarlo.');
        }

        $turno->delete();

        return redirect()
            ->route('nomina.turnos.index')
            ->with('success', 'Turno eliminado correctamente.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validar(Request $request, ?Turno $turno = null): array
    {
        $request->merge([
            'nombre' => mb_strtoupper(trim((string) $request->input('nombre'))),
            'activo' => $request->boolean('activo'),
        ]);

        return $request->validate([
            'nombre' => ['required', 'string', 'max:30', Rule::unique('nomina_turnos', 'nombre')->ignore($turno?->id)],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'different:hora_inicio'],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'activo' => ['boolean'],
        ], [
            'hora_fin.different' => 'La hora final debe ser distinta de la inicial.',
        ], [
            'hora_inicio' => 'hora de inicio',
            'hora_fin' => 'hora final',
        ]);
    }
}
