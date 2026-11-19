<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Models\Nomina\LiquidacionNomina;
use App\Services\Nomina\NominaLiquidacionService;
use App\Services\Nomina\PrestacionesSocialesService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Prima semestral (liquidador masivo) y consolidación anual de cesantías, intereses y vacaciones.
 * La prima y los intereses calculados se pueden llevar a una nómina en borrador para pagarlos.
 */
class PrestacionSocialController extends Controller
{
    public function __construct(private PrestacionesSocialesService $prestaciones) {}

    public function prima(Request $request): View
    {
        $request->validate([
            'anio' => ['nullable', 'integer', 'min:2020', 'max:2100'],
            'semestre' => ['nullable', 'integer', Rule::in([1, 2])],
        ]);
        $anio = $request->integer('anio') ?: now()->year;
        $semestre = $request->integer('semestre') ?: (now()->month <= 6 ? 1 : 2);

        return view('nomina.prestaciones.prima', [
            'anio' => $anio,
            'semestre' => $semestre,
            'filas' => $this->prestaciones->primaSemestral($anio, $semestre),
            'nominasBorrador' => $this->nominasBorrador(),
            'nominaSugerida' => LiquidacionNomina::query()->where('anio', $anio)->where('mes', $semestre === 1 ? 6 : 12)->where('estado', LiquidacionNomina::ESTADO_BORRADOR)->value('id'),
        ]);
    }

    public function consolidacion(Request $request): View
    {
        $request->validate(['anio' => ['nullable', 'integer', 'min:2020', 'max:2100']]);
        $anio = $request->integer('anio') ?: now()->year;

        return view('nomina.prestaciones.consolidacion', [
            'anio' => $anio,
            'filas' => $this->prestaciones->consolidacion($anio),
            'nominasBorrador' => $this->nominasBorrador(),
            'nominaSugerida' => LiquidacionNomina::query()->where('anio', $anio + 1)->where('mes', 1)->where('estado', LiquidacionNomina::ESTADO_BORRADOR)->value('id'),
        ]);
    }

    /**
     * Lleva la prima o los intereses de cesantías calculados a una nómina en borrador.
     */
    public function aplicar(Request $request, NominaLiquidacionService $nomina): RedirectResponse
    {
        $validated = $request->validate([
            'concepto' => ['required', Rule::in(['prima', 'intereses_cesantias'])],
            'liquidacion_id' => ['required', 'integer', Rule::exists('nomina_liquidaciones', 'id')->where('estado', LiquidacionNomina::ESTADO_BORRADOR)],
            'valores' => ['required', 'array'],
            'valores.*' => ['numeric', 'min:0'],
        ], [
            'liquidacion_id.required' => 'Elija la nómina en borrador donde se pagará.',
            'liquidacion_id.exists' => 'La nómina elegida no existe o ya está aprobada.',
        ]);

        $liquidacion = LiquidacionNomina::findOrFail($validated['liquidacion_id']);
        $aplicados = 0;

        DB::transaction(function () use ($liquidacion, $validated, &$aplicados): void {
            foreach ($liquidacion->empleados()->get() as $fila) {
                if (array_key_exists($fila->empleado_id, $validated['valores'])) {
                    $fila->update([$validated['concepto'] => round((float) $validated['valores'][$fila->empleado_id])]);
                    $aplicados++;
                }
            }
        });
        $nomina->recalcular($liquidacion);

        $concepto = $validated['concepto'] === 'prima' ? 'Prima' : 'Intereses de cesantías';

        return redirect()->route('nomina.nominas.show', $liquidacion)
            ->with('success', $concepto.' llevada a la nómina de '.$liquidacion->nombreDelMes().' para '.$aplicados.' empleado(s).');
    }

    /**
     * @return Collection<int, LiquidacionNomina>
     */
    private function nominasBorrador(): Collection
    {
        return LiquidacionNomina::query()->where('estado', LiquidacionNomina::ESTADO_BORRADOR)->orderByDesc('anio')->orderByDesc('mes')->get();
    }
}
