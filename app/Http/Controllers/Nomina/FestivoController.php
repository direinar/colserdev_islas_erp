<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Models\Nomina\Festivo;
use App\Services\Nomina\FestivosColombiaService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FestivoController extends Controller
{
    public function index(Request $request): View
    {
        $anio = (int) $request->query('anio', now()->year);

        $festivos = Festivo::query()
            ->whereYear('fecha', $anio)
            ->orderBy('fecha')
            ->get();

        return view('nomina.festivos.index', compact('festivos', 'anio'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'fecha' => ['required', 'date', function (string $attribute, mixed $value, \Closure $fail): void {
                if (Festivo::whereDate('fecha', $value)->exists()) {
                    $fail('Esa fecha ya está registrada como festivo.');
                }
            }],
            'nombre' => ['required', 'string', 'max:255'],
        ]);

        $festivo = Festivo::create($validated);

        return redirect()
            ->route('nomina.festivos.index', ['anio' => $festivo->fecha->year])
            ->with('success', 'Festivo agregado correctamente.');
    }

    /**
     * Carga los festivos de ley del año sin tocar los que ya existen.
     */
    public function generar(Request $request, FestivosColombiaService $festivosColombia): RedirectResponse
    {
        $validated = $request->validate([
            'anio' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        $creados = 0;
        foreach ($festivosColombia->festivosDelAnio((int) $validated['anio']) as $fecha => $nombre) {
            if (Festivo::whereDate('fecha', $fecha)->doesntExist()) {
                Festivo::create(['fecha' => $fecha, 'nombre' => $nombre]);
                $creados++;
            }
        }

        return redirect()
            ->route('nomina.festivos.index', ['anio' => $validated['anio']])
            ->with('success', "Se cargaron {$creados} festivos de {$validated['anio']}.");
    }

    public function destroy(Festivo $festivo): RedirectResponse
    {
        $anio = $festivo->fecha->year;
        $festivo->delete();

        return redirect()
            ->route('nomina.festivos.index', ['anio' => $anio])
            ->with('success', 'Festivo eliminado correctamente.');
    }
}
