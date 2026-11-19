<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Models\Nomina\Dotacion;
use App\Models\Nomina\Empleado;
use App\Models\Nomina\ParametroLiquidacion;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Formato de control de dotación: tres entregas al año (30 abr, 31 ago, 20 dic) para quienes
 * ganan hasta dos salarios mínimos.
 */
class DotacionController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate(['anio' => ['nullable', 'integer', 'min:2020', 'max:2100']]);
        $anio = $request->integer('anio') ?: now()->year;
        $minimo = (float) (ParametroLiquidacion::vigenteEn(CarbonImmutable::create($anio, 12, 31))?->salario_minimo ?? 0);

        $empleados = Empleado::query()
            ->vinculadosEntre($anio.'-01-01', $anio.'-12-31')
            ->with(['dotaciones' => fn ($query) => $query->where('anio', $anio)])
            ->orderBy('nombres')
            ->get();

        $entregas = collect(Dotacion::ENTREGAS)->map(fn (array $entrega): array => [
            'nombre' => $entrega[0],
            'limite' => CarbonImmutable::parse($anio.'-'.$entrega[1]),
        ]);

        return view('nomina.dotaciones.index', [
            'anio' => $anio,
            'empleados' => $empleados,
            'entregas' => $entregas,
            'topeDerecho' => $minimo * 2,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'empleado_id' => ['required', 'integer', 'exists:nomina_empleados,id'],
            'anio' => ['required', 'integer', 'min:2020', 'max:2100'],
            'entrega' => [
                'required', 'integer', Rule::in(array_keys(Dotacion::ENTREGAS)),
                Rule::unique('nomina_dotaciones', 'entrega')->where('empleado_id', $request->integer('empleado_id'))->where('anio', $request->integer('anio')),
            ],
            'fecha_entrega' => ['required', 'date'],
            'elementos' => ['required', 'string', 'max:500'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ], [
            'entrega.unique' => 'Esa entrega ya está registrada para el empleado.',
        ]);

        Dotacion::create($validated);

        return redirect()->route('nomina.dotaciones.index', ['anio' => $validated['anio']])->with('success', 'Entrega de dotación registrada.');
    }

    public function destroy(Dotacion $dotacion): RedirectResponse
    {
        $anio = $dotacion->anio;
        $dotacion->delete();

        return redirect()->route('nomina.dotaciones.index', ['anio' => $anio])->with('success', 'Entrega de dotación eliminada.');
    }
}
