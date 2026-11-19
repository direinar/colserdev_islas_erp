<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Módulo de Contabilidad: en construcción. La estructura (rutas /contabilidad, permisos y
 * selector de módulo) queda lista para desarrollarlo.
 */
class ContabilidadController extends Controller
{
    public function index(): View
    {
        return view('contabilidad.index');
    }
}
