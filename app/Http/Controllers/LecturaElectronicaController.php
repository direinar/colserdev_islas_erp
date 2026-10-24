<?php

namespace App\Http\Controllers;

use App\Services\LecturaElectronicaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Informe de lectura electrónica de los surtidores por fecha: lecturas de las mangueras (A),
 * venta según cierres de IAPROPIADA del día (B) y sobrante/faltante por lectura (A - B).
 */
class LecturaElectronicaController extends Controller
{
    public function index(Request $request, LecturaElectronicaService $lecturas): View
    {
        $request->validate(['fecha' => ['nullable', 'date']]);

        return view('lectura-electronica.index', $lecturas->informe($request->query('fecha') ?: now()->toDateString()));
    }

    public function guardar(Request $request, LecturaElectronicaService $lecturas): RedirectResponse
    {
        $validated = $request->validate([
            'fecha' => ['required', 'date'],
            'iniciales' => ['nullable', 'array'],
            'iniciales.*' => ['nullable', 'string', 'max:30'],
            'finales' => ['nullable', 'array'],
            'finales.*' => ['nullable', 'string', 'max:30'],
        ]);

        $lecturas->guardar($validated['fecha'], $validated['iniciales'] ?? [], $validated['finales'] ?? [], $request->user());

        return redirect()->route('lectura-electronica.index', ['fecha' => $validated['fecha']])
            ->with('success', 'Lectura electrónica guardada.');
    }
}
