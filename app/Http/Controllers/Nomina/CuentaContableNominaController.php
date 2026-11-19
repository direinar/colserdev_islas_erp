<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Models\Nomina\CuentaContableNomina;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Configuración → Cuentas contables de la nómina (hoja CONTABILIZACION).
 */
class CuentaContableNominaController extends Controller
{
    public function index(): View
    {
        return view('nomina.cuentas-contables.index', [
            'cuentas' => CuentaContableNomina::query()->orderBy('orden')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cuentas' => ['required', 'array'],
            'cuentas.*' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]*$/'],
        ], [
            'cuentas.*.regex' => 'Las cuentas contables solo llevan números.',
        ]);

        foreach (CuentaContableNomina::query()->get() as $cuenta) {
            if (array_key_exists($cuenta->id, $validated['cuentas'])) {
                $cuenta->update(['cuenta' => $validated['cuentas'][$cuenta->id] ?: null]);
            }
        }

        return redirect()->route('nomina.cuentas-contables.index')->with('success', 'Cuentas contables guardadas.');
    }
}
