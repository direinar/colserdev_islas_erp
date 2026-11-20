<?php

namespace App\Http\Controllers;

use App\Models\ConteoCuenta;
use App\Models\ConteoDestino;
use App\Models\ConteoTercero;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Tablas del conteo: destinos (conceptos) y bancos y terceros de la consignación en bancos y
 * otras aplicaciones. Un registro ya usado en un conteo no se elimina.
 */
class ConteoCatalogoController extends Controller
{
    public function index(): View
    {
        return view('conteos.catalogos', [
            'destinos' => ConteoDestino::query()->withCount('aplicaciones')->orderBy('nombre')->get(),
            'terceros' => ConteoTercero::query()->withCount('aplicaciones')->orderBy('nombre')->get(),
            'cuentas' => ConteoCuenta::query()->withCount('aplicaciones')->orderBy('cuenta')->orderBy('concepto')->get(),
        ]);
    }

    public function guardarDestino(Request $request, ?ConteoDestino $destino = null): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:120', Rule::unique('conteo_destinos', 'nombre')->ignore($destino)],
        ]);

        $destino ? $destino->update($validated) : ConteoDestino::create($validated);

        return redirect()->route('conteos.catalogos')->with('success', 'Destino guardado.');
    }

    public function eliminarDestino(ConteoDestino $destino): RedirectResponse
    {
        if ($destino->aplicaciones()->exists()) {
            return redirect()->route('conteos.catalogos')->withErrors(['catalogo' => 'El destino "'.$destino->nombre.'" ya se usó en un conteo y no se puede eliminar.']);
        }

        $destino->delete();

        return redirect()->route('conteos.catalogos')->with('success', 'Destino eliminado.');
    }

    public function guardarTercero(Request $request, ?ConteoTercero $tercero = null): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:120', Rule::unique('conteo_terceros', 'nombre')->ignore($tercero)],
            'tipo' => ['required', Rule::in(array_keys(ConteoTercero::TIPOS))],
        ]);

        $tercero ? $tercero->update($validated) : ConteoTercero::create($validated);

        return redirect()->route('conteos.catalogos')->with('success', 'Banco o tercero guardado.');
    }

    public function eliminarTercero(ConteoTercero $tercero): RedirectResponse
    {
        if ($tercero->aplicaciones()->exists()) {
            return redirect()->route('conteos.catalogos')->withErrors(['catalogo' => '"'.$tercero->nombre.'" ya se usó en un conteo y no se puede eliminar.']);
        }

        $tercero->delete();

        return redirect()->route('conteos.catalogos')->with('success', 'Banco o tercero eliminado.');
    }

    /**
     * Tabla de cuentas, conceptos, terceros y NIT del comprobante contable del conteo.
     */
    public function guardarCuenta(Request $request, ?ConteoCuenta $cuenta = null): RedirectResponse
    {
        $validated = $request->validate([
            'cuenta' => ['required', 'string', 'max:20', 'regex:/^[0-9]+$/'],
            'concepto' => ['required', 'string', 'max:150', Rule::unique('conteo_cuentas', 'concepto')->where('cuenta', $request->input('cuenta'))->ignore($cuenta)],
            'tercero' => ['nullable', 'string', 'max:120'],
            'nit' => ['nullable', 'string', 'max:30'],
        ], [
            'cuenta.regex' => 'La cuenta contable solo lleva números.',
            'concepto.unique' => 'Esa cuenta ya tiene ese concepto en la tabla.',
        ]);
        $validated['concepto'] = mb_strtoupper(trim($validated['concepto']));
        $validated['tercero'] = filled($validated['tercero'] ?? null) ? mb_strtoupper(trim($validated['tercero'])) : null;

        $cuenta ? $cuenta->update($validated) : ConteoCuenta::create($validated);

        return redirect()->to(route('conteos.catalogos').'#cuentas')->with('success', 'Cuenta contable guardada.');
    }

    public function eliminarCuenta(ConteoCuenta $cuenta): RedirectResponse
    {
        if ($cuenta->aplicaciones()->exists()) {
            return redirect()->to(route('conteos.catalogos').'#cuentas')
                ->withErrors(['catalogo' => 'La cuenta '.$cuenta->cuenta.' ('.$cuenta->concepto.') ya se usó en un comprobante y no se puede eliminar.']);
        }

        $cuenta->delete();

        return redirect()->to(route('conteos.catalogos').'#cuentas')->with('success', 'Cuenta contable eliminada.');
    }
}
