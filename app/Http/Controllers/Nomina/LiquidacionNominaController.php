<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Models\Nomina\LiquidacionNomina;
use App\Models\Nomina\LiquidacionNominaEmpleado;
use App\Services\Nomina\NominaLiquidacionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Nómina del mes: novedades, liquidación por empleado, forma de pago, contabilización y desprendibles.
 */
class LiquidacionNominaController extends Controller
{
    public function __construct(private NominaLiquidacionService $nomina) {}

    public function index(): View
    {
        $liquidaciones = LiquidacionNomina::query()
            ->withCount('empleados')
            ->withSum('empleados as neto_total', 'neto_pagar')
            ->withSum('empleados as costo_total', 'costo_total')
            ->orderByDesc('anio')
            ->orderByDesc('mes')
            ->get();

        $siguiente = $liquidaciones->first()
            ? $liquidaciones->first()->inicioDelMes()->addMonth()
            : now()->startOfMonth();

        return view('nomina.liquidaciones.index', compact('liquidaciones', 'siguiente'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'anio' => ['required', 'integer', 'min:2020', 'max:2100'],
            'mes' => [
                'required', 'integer', 'min:1', 'max:12',
                Rule::unique('nomina_liquidaciones', 'mes')->where('anio', $request->integer('anio')),
            ],
        ], [
            'mes.unique' => 'Ya existe la nómina de ese mes.',
        ]);

        $liquidacion = $this->nomina->crear((int) $validated['anio'], (int) $validated['mes']);

        return redirect()
            ->route('nomina.nominas.show', $liquidacion)
            ->with('success', 'Nómina de '.$liquidacion->nombreDelMes().' creada con '.$liquidacion->empleados()->count().' empleado(s). Revise las novedades y guarde.');
    }

    public function show(LiquidacionNomina $nomina): View
    {
        $nomina->load(['parametros', 'empleados.empleado']);
        $nomina->setRelation('empleados', $nomina->empleados->sortBy(fn (LiquidacionNominaEmpleado $fila): string => $fila->empleado->nombreParaNomina())->values());

        return view('nomina.liquidaciones.show', [
            'liquidacion' => $nomina,
            'totales' => $this->nomina->totales($nomina),
            'formaPago' => $this->nomina->formaPago($nomina),
            'contabilizacion' => $this->nomina->contabilizacion($nomina),
            'alertas' => $this->nomina->alertas($nomina),
            'mediosPago' => $this->nomina->mediosPago(),
            'planilla' => $nomina->planillaTurnos(),
        ]);
    }

    /**
     * Guarda las novedades del mes y recalcula.
     */
    public function update(Request $request, LiquidacionNomina $nomina): RedirectResponse
    {
        if ($nomina->estaAprobada()) {
            return back()->with('error', 'La nómina está aprobada: reábrala para modificarla.');
        }

        $validated = $request->validate([
            'filas' => ['nullable', 'array'],
            'filas.*.dias_trabajados' => ['required', 'integer', 'min:0', 'max:30'],
            'filas.*.dias_vacaciones_disfrutadas' => ['nullable', 'integer', 'min:0', 'max:30'],
            'filas.*.dias_vacaciones_dinero' => ['nullable', 'integer', 'min:0', 'max:90'],
            'filas.*.horas_nocturnas' => ['nullable', 'numeric', 'min:0', 'max:400'],
            'filas.*.horas_festivas' => ['nullable', 'numeric', 'min:0', 'max:400'],
            'filas.*.cesantias' => ['nullable', 'numeric', 'min:0'],
            'filas.*.intereses_cesantias' => ['nullable', 'numeric', 'min:0'],
            'filas.*.prima' => ['nullable', 'numeric', 'min:0'],
            'filas.*.anticipos' => ['nullable', 'numeric', 'min:0'],
            'filas.*.faltantes' => ['nullable', 'numeric', 'min:0'],
            'filas.*.medio_pago' => ['nullable', 'string', 'max:60'],
            'filas.*.observaciones' => ['nullable', 'string', 'max:500'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ], [
            'filas.*.dias_trabajados.max' => 'Los días trabajados van de 0 a 30.',
        ]);

        $filas = collect($validated['filas'] ?? [])->map(fn (array $fila): array => array_map(
            fn (mixed $valor): mixed => $valor === null ? 0 : $valor,
            array_diff_key($fila, array_flip(['medio_pago', 'observaciones'])),
        ) + ['medio_pago' => $fila['medio_pago'] ?? null, 'observaciones' => $fila['observaciones'] ?? null])->all();

        $this->nomina->guardarNovedades($nomina, $filas);
        $nomina->update(['observaciones' => $validated['observaciones'] ?? null]);

        return redirect()->route('nomina.nominas.show', $nomina)->with('success', 'Novedades guardadas y nómina recalculada.');
    }

    public function recargos(LiquidacionNomina $nomina): RedirectResponse
    {
        if ($nomina->estaAprobada()) {
            return back()->with('error', 'La nómina está aprobada: reábrala para modificarla.');
        }

        return $this->nomina->traerRecargos($nomina)
            ? back()->with('success', 'Horas nocturnas y festivas tomadas de la planilla de turnos de '.$nomina->nombreDelMes().'.')
            : back()->with('error', 'No existe la planilla de turnos de '.$nomina->nombreDelMes().'.');
    }

    public function sincronizar(LiquidacionNomina $nomina): RedirectResponse
    {
        if ($nomina->estaAprobada()) {
            return back()->with('error', 'La nómina está aprobada: reábrala para modificarla.');
        }

        $agregados = $this->nomina->sincronizarEmpleados($nomina);

        return back()->with('success', $agregados > 0 ? $agregados.' empleado(s) agregado(s) a la nómina.' : 'La nómina ya tiene a todos los empleados vinculados en el mes.');
    }

    public function quitarEmpleado(LiquidacionNomina $nomina, LiquidacionNominaEmpleado $fila): RedirectResponse
    {
        abort_unless($fila->liquidacion_id === $nomina->id, 404);

        if ($nomina->estaAprobada()) {
            return back()->with('error', 'La nómina está aprobada: reábrala para modificarla.');
        }

        $nombre = $fila->empleado->nombreParaNomina();
        $fila->delete();

        return back()->with('success', $nombre.' se quitó de la nómina.');
    }

    public function aprobar(Request $request, LiquidacionNomina $nomina): RedirectResponse
    {
        $this->nomina->recalcular($nomina);
        $formaPago = $this->nomina->formaPago($nomina);

        if ($nomina->empleados()->doesntExist()) {
            return back()->with('error', 'La nómina no tiene empleados.');
        }

        if ($nomina->empleados()->where('neto_pagar', '<', 0)->exists() || $nomina->empleados()->whereNull('medio_pago')->exists()) {
            return back()->with('error', 'Revise los avisos: hay empleados con neto negativo o sin medio de pago.');
        }

        $nomina->update([
            'estado' => LiquidacionNomina::ESTADO_APROBADA,
            'aprobada_por' => $request->user()->name,
            'aprobada_at' => now(),
        ]);

        return back()->with('success', 'Nómina de '.$nomina->nombreDelMes().' aprobada. Neto a pagar $'.number_format($formaPago['neto'], 0, ',', '.').'.');
    }

    public function reabrir(LiquidacionNomina $nomina): RedirectResponse
    {
        $nomina->update(['estado' => LiquidacionNomina::ESTADO_BORRADOR, 'aprobada_por' => null, 'aprobada_at' => null]);

        return back()->with('success', 'Nómina de '.$nomina->nombreDelMes().' devuelta a borrador.');
    }

    public function destroy(LiquidacionNomina $nomina): RedirectResponse
    {
        if ($nomina->estaAprobada()) {
            return back()->with('error', 'Una nómina aprobada no se elimina: reábrala primero.');
        }

        $nombre = $nomina->nombreDelMes();
        $nomina->delete();

        return redirect()->route('nomina.nominas.index')->with('success', 'Nómina de '.$nombre.' eliminada.');
    }

    /**
     * Desprendibles para imprimir: todos o el de un empleado (?fila=).
     */
    public function desprendibles(Request $request, LiquidacionNomina $nomina): View
    {
        return view('nomina.liquidaciones.desprendibles', $this->datosDesprendibles($request, $nomina) + ['paraPdf' => false]);
    }

    public function desprendiblesPdf(Request $request, LiquidacionNomina $nomina): Response
    {
        $datos = $this->datosDesprendibles($request, $nomina);
        $nombre = $datos['filas']->count() === 1
            ? 'desprendible-'.Str::slug($datos['filas']->first()->empleado->nombreParaNomina()).'-'.$nomina->anio.'-'.str_pad((string) $nomina->mes, 2, '0', STR_PAD_LEFT)
            : 'desprendibles-nomina-'.$nomina->anio.'-'.str_pad((string) $nomina->mes, 2, '0', STR_PAD_LEFT);

        return Pdf::loadView('nomina.liquidaciones.desprendibles', $datos + ['paraPdf' => true])
            ->setPaper('letter')
            ->download($nombre.'.pdf');
    }

    /**
     * @return array{liquidacion: LiquidacionNomina, filas: Collection<int, LiquidacionNominaEmpleado>}
     */
    private function datosDesprendibles(Request $request, LiquidacionNomina $nomina): array
    {
        $filas = $nomina->empleados()->with('empleado')
            ->when($request->integer('fila'), fn ($query, int $fila) => $query->whereKey($fila))
            ->get()
            ->sortBy(fn (LiquidacionNominaEmpleado $fila): string => $fila->empleado->nombreParaNomina())
            ->values();

        abort_if($filas->isEmpty(), 404);

        return ['liquidacion' => $nomina->load('parametros'), 'filas' => $filas];
    }
}
