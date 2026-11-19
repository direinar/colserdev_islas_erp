<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Models\Nomina\ParametroLiquidacion;
use App\Support\NumberParser;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ParametroLiquidacionController extends Controller
{
    public function index(): View
    {
        $parametros = ParametroLiquidacion::orderByDesc('vigente_desde')->get();
        $vigente = ParametroLiquidacion::vigenteEn(now());

        return view('nomina.parametros-liquidacion.index', compact('parametros', 'vigente'));
    }

    /**
     * El formulario nuevo parte de la vigencia más reciente: normalmente solo cambian
     * el salario mínimo, el auxilio de transporte y algún porcentaje.
     */
    public function create(): View
    {
        $base = ParametroLiquidacion::orderByDesc('vigente_desde')->first();
        $parametro = $base ? $base->replicate(['vigente_desde', 'descripcion']) : null;

        return view('nomina.parametros-liquidacion.create', compact('parametro'));
    }

    public function store(Request $request): RedirectResponse
    {
        $parametro = ParametroLiquidacion::create($this->validar($request));

        return redirect()
            ->route('nomina.parametros-liquidacion.show', $parametro)
            ->with('success', 'Vigencia de parámetros creada correctamente.');
    }

    public function show(ParametroLiquidacion $parametros_liquidacion): View
    {
        return view('nomina.parametros-liquidacion.show', ['parametro' => $parametros_liquidacion]);
    }

    public function edit(ParametroLiquidacion $parametros_liquidacion): View
    {
        return view('nomina.parametros-liquidacion.edit', ['parametro' => $parametros_liquidacion]);
    }

    public function update(Request $request, ParametroLiquidacion $parametros_liquidacion): RedirectResponse
    {
        $parametros_liquidacion->update($this->validar($request, $parametros_liquidacion));

        return redirect()
            ->route('nomina.parametros-liquidacion.show', $parametros_liquidacion)
            ->with('success', 'Vigencia de parámetros actualizada correctamente.');
    }

    public function destroy(ParametroLiquidacion $parametros_liquidacion): RedirectResponse
    {
        $parametros_liquidacion->delete();

        return redirect()
            ->route('nomina.parametros-liquidacion.index')
            ->with('success', 'Vigencia de parámetros eliminada correctamente.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validar(Request $request, ?ParametroLiquidacion $parametro = null): array
    {
        $request->merge([
            'salario_minimo' => NumberParser::money($request->input('salario_minimo')),
            'auxilio_transporte' => NumberParser::money($request->input('auxilio_transporte')),
        ]);

        $porcentajes = [];
        foreach (ParametroLiquidacion::gruposPorcentajes() as $campos) {
            foreach (array_keys($campos) as $campo) {
                $porcentajes[$campo] = ['required', 'numeric', 'min:0', 'max:999'];
            }
        }

        return $request->validate([
            'vigente_desde' => ['required', 'date', function (string $attribute, mixed $value, \Closure $fail) use ($parametro): void {
                $existe = ParametroLiquidacion::whereDate('vigente_desde', $value)
                    ->when($parametro, fn ($query) => $query->whereKeyNot($parametro->id))
                    ->exists();

                if ($existe) {
                    $fail('Ya existe una vigencia que empieza en esa fecha.');
                }
            }],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'salario_minimo' => ['required', 'numeric', 'gt:0'],
            'auxilio_transporte' => ['required', 'numeric', 'min:0'],
            'horas_mensuales' => ['required', 'integer', 'min:1', 'max:744'],
            'horas_semanales' => ['required', 'integer', 'min:1', 'max:168'],
            'hora_inicio_nocturna' => ['required', 'date_format:H:i'],
            'hora_fin_nocturna' => ['required', 'date_format:H:i'],
            'hora_inicio_festiva' => ['required', 'date_format:H:i'],
            'hora_fin_festiva' => ['required', 'date_format:H:i'],
            ...$porcentajes,
        ], [], [
            'vigente_desde' => 'vigente desde',
            'salario_minimo' => 'salario mínimo',
            'auxilio_transporte' => 'auxilio de transporte',
            'horas_mensuales' => 'horas mensuales',
            'horas_semanales' => 'horas semanales',
        ]);
    }
}
