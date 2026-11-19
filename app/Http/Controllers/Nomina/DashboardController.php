<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Models\Nomina\Empleado;
use App\Models\Nomina\LiquidacionNomina;
use App\Models\Nomina\ParametroLiquidacion;
use App\Models\Nomina\PlanillaTurno;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Inicio del módulo de Nómina: indicadores de empleados, planillas de turno y parámetros.
 */
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        return view('nomina.dashboard', [
            'usuario' => $request->user(),
            'empleadosActivos' => Empleado::query()->activos()->count(),
            'contratosPorVencer' => Empleado::query()->activos()->get()->filter(fn (Empleado $empleado): bool => $empleado->contratoPorVencer())->count(),
            'planillasAbiertas' => PlanillaTurno::query()->where('estado', PlanillaTurno::ESTADO_BORRADOR)->count(),
            'parametrosVigentes' => ParametroLiquidacion::vigenteEn(now()),
            'ultimaNomina' => LiquidacionNomina::query()->withSum('empleados as neto_total', 'neto_pagar')->orderByDesc('anio')->orderByDesc('mes')->first(),
        ]);
    }
}
