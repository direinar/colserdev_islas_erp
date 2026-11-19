<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Models\Nomina\Empleado;
use App\Models\Nomina\LiquidacionContrato;
use App\Services\Nomina\PrestacionesSocialesService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Liquidación del contrato de trabajo y prestaciones sociales al retiro (liquidador individual).
 */
class LiquidacionContratoController extends Controller
{
    public function __construct(private PrestacionesSocialesService $prestaciones) {}

    public function index(): View
    {
        return view('nomina.liquidaciones-contrato.index', [
            'liquidaciones' => LiquidacionContrato::query()->with('empleado')->latest('fecha_retiro')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $empleados = Empleado::query()->orderBy('nombres')->get();
        $empleado = $request->integer('empleado') ? $empleados->firstWhere('id', $request->integer('empleado')) : null;

        return view('nomina.liquidaciones-contrato.create', [
            'empleados' => $empleados,
            'empleado' => $empleado,
            'motivos' => self::motivos(),
            'baseSugerida' => $empleado ? $this->prestaciones->salarioBaseLiquidacion($empleado, CarbonImmutable::parse($request->input('fecha_retiro', now()->toDateString()))) : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'empleado_id' => ['required', 'integer', 'exists:nomina_empleados,id'],
            'fecha_retiro' => ['required', 'date'],
            'motivo_retiro' => ['required', 'string', 'max:150'],
            'sueldo_pendiente' => ['nullable', 'numeric', 'min:0'],
            'recargos_nocturnos' => ['nullable', 'numeric', 'min:0'],
            'recargos_festivos' => ['nullable', 'numeric', 'min:0'],
            'auxilio_transporte' => ['nullable', 'numeric', 'min:0'],
            'otras_deducciones' => ['nullable', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'registrar_retiro' => ['nullable', 'boolean'],
        ]);

        $empleado = Empleado::findOrFail($validated['empleado_id']);
        $fechaRetiro = CarbonImmutable::parse($validated['fecha_retiro']);

        if ($empleado->fecha_ingreso === null || $fechaRetiro->lt($empleado->fecha_ingreso)) {
            return back()->withInput()->withErrors(['fecha_retiro' => 'La fecha de retiro debe ser posterior a la fecha de ingreso del empleado.']);
        }

        $liquidacion = DB::transaction(function () use ($empleado, $fechaRetiro, $validated, $request): LiquidacionContrato {
            $liquidacion = $empleado->liquidacionesContrato()->create(
                $this->prestaciones->liquidarContrato($empleado, $fechaRetiro, $validated) + [
                    'motivo_retiro' => $validated['motivo_retiro'],
                    'observaciones' => $validated['observaciones'] ?? null,
                    'elaborada_por' => $request->user()->name,
                ]
            );

            if ($request->boolean('registrar_retiro')) {
                $empleado->update(['activo' => false, 'fecha_retiro' => $fechaRetiro->toDateString(), 'motivo_retiro' => $validated['motivo_retiro']]);
            }

            return $liquidacion;
        });

        return redirect()->route('nomina.liquidaciones-contrato.show', $liquidacion)->with('success', 'Liquidación de '.$empleado->nombre_completo.' elaborada.');
    }

    public function show(Request $request, LiquidacionContrato $liquidacion): View|Response
    {
        $datos = ['liquidacion' => $liquidacion->load('empleado')];

        if ($request->boolean('pdf')) {
            return Pdf::loadView('nomina.liquidaciones-contrato.documento', $datos + ['paraPdf' => true])
                ->setPaper('letter')
                ->download('liquidacion-contrato-'.Str::slug($liquidacion->empleado->nombre_completo).'.pdf');
        }

        return view('nomina.liquidaciones-contrato.show', $datos);
    }

    public function destroy(LiquidacionContrato $liquidacion): RedirectResponse
    {
        $liquidacion->delete();

        return redirect()->route('nomina.liquidaciones-contrato.index')->with('success', 'Liquidación eliminada.');
    }

    /**
     * @return list<string>
     */
    public static function motivos(): array
    {
        return [
            'Finalización de contrato a término fijo',
            'Renuncia voluntaria',
            'Terminación con justa causa',
            'Terminación sin justa causa',
            'Mutuo acuerdo',
            'Finalización de obra o labor',
            'Periodo de prueba',
        ];
    }
}
