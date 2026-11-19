<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Models\Nomina\Empleado;
use App\Models\Nomina\LiquidacionNomina;
use App\Services\Nomina\PrestacionesSocialesService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Acumulados anuales por empleado (hoja ACUMULADORES) y certificado de ingresos del año.
 */
class AcumuladoNominaController extends Controller
{
    public function __construct(private PrestacionesSocialesService $prestaciones) {}

    public function index(Request $request): View
    {
        $anio = $this->anio($request);

        return view('nomina.acumulados.index', [
            'anio' => $anio,
            'anios' => $this->anios(),
            'acumulados' => $this->prestaciones->acumulados($anio),
            'mesesAprobados' => LiquidacionNomina::query()->where('anio', $anio)->where('estado', LiquidacionNomina::ESTADO_APROBADA)->orderBy('mes')->pluck('mes'),
        ]);
    }

    /**
     * Certificado de ingresos del año de un empleado (para imprimir o PDF con ?pdf=1).
     */
    public function certificado(Request $request, Empleado $empleado): View|Response
    {
        $anio = $this->anio($request);
        $filas = $this->prestaciones->filasAprobadas($anio, 1, 12, $empleado->id);
        abort_if($filas->isEmpty(), 404, 'El empleado no tiene nóminas aprobadas en '.$anio.'.');

        $datos = [
            'empleado' => $empleado,
            'anio' => $anio,
            'acumulado' => $this->prestaciones->sumarAcumulado($filas),
            'meses' => $filas->sortBy(fn ($fila) => $fila->liquidacion->mes)->values(),
        ];

        if ($request->boolean('pdf')) {
            return Pdf::loadView('nomina.acumulados.certificado', $datos + ['paraPdf' => true])
                ->setPaper('letter')
                ->download('certificado-ingresos-'.$anio.'-'.Str::slug($empleado->nombre_completo).'.pdf');
        }

        return view('nomina.acumulados.certificado', $datos + ['paraPdf' => false]);
    }

    private function anio(Request $request): int
    {
        $request->validate(['anio' => ['nullable', 'integer', 'min:2020', 'max:2100']]);

        return $request->integer('anio') ?: (int) (LiquidacionNomina::query()->max('anio') ?? now()->year);
    }

    /**
     * @return list<int>
     */
    private function anios(): array
    {
        return LiquidacionNomina::query()->distinct()->orderByDesc('anio')->pluck('anio')->push(now()->year)->unique()->sortDesc()->values()->all();
    }
}
