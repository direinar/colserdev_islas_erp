<?php

namespace App\Http\Controllers;

use App\Concerns\ResuelveRangoFechas;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use ResuelveRangoFechas;

    public function index(Request $request, DashboardService $dashboard): View|RedirectResponse
    {
        // El contador solo trabaja en Nómina (y Contabilidad): entra al dashboard de nómina.
        if (! $request->user()->canAccessModulo(User::MODULO_OPERACIONES)) {
            return redirect()->route('nomina.dashboard');
        }

        // El jefe de patios y el islero no tienen dashboard: entran a la planilla de turnos.
        if (! $request->user()->canAccessDashboard()) {
            return redirect()->route('turnos.create');
        }

        [$desde, $hasta] = $this->rango($request);

        return view('dashboard.index', [
            'desde' => $desde,
            'hasta' => $hasta,
        ] + $dashboard->resumen($desde, $hasta, $request->user()));
    }
}
