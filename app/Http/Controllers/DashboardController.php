<?php

namespace App\Http\Controllers;

use App\Concerns\ResuelveRangoFechas;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use ResuelveRangoFechas;

    public function index(Request $request, DashboardService $dashboard): View
    {
        [$desde, $hasta] = $this->rango($request);

        return view('dashboard.index', [
            'desde' => $desde,
            'hasta' => $hasta,
        ] + $dashboard->resumen($desde, $hasta, $request->user()));
    }
}
