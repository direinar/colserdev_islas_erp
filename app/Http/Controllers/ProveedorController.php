<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use App\Support\DescargaExcel;
use App\Support\TablaExcel;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProveedorController extends Controller
{
    public function index()
    {
        $proveedores = Proveedor::latest()->get();

        return view('proveedores.index', compact('proveedores'));
    }

    /**
     * Proveedores en Excel.
     */
    public function exportar(): StreamedResponse
    {
        $filas = Proveedor::query()->orderBy('name')->get()
            ->map(fn (Proveedor $proveedor): array => [$proveedor->name, (string) $proveedor->document]);

        return DescargaExcel::respuesta(
            TablaExcel::generar('Proveedores', 'Generado el '.now()->format('d/m/Y'), ['NOMBRE', 'DOCUMENTO'], $filas, [0, 1]),
            'proveedores.xlsx',
        );
    }

    public function create()
    {
        return view('proveedores.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'document' => 'required|string|max:100',
        ]);

        Proveedor::create($validated);

        return redirect()
            ->route('proveedores.index')
            ->with('success', 'Proveedor creado correctamente.');
    }

    public function show(Proveedor $proveedor)
    {
        return view('proveedores.show', compact('proveedor'));
    }

    public function edit(Proveedor $proveedor)
    {
        return view('proveedores.edit', compact('proveedor'));
    }

    public function update(Request $request, Proveedor $proveedor)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'document' => 'required|string|max:100',
        ]);

        $proveedor->update($validated);

        return redirect()
            ->route('proveedores.index')
            ->with('success', 'Proveedor actualizado correctamente.');
    }

    public function destroy(Proveedor $proveedor)
    {
        $proveedor->delete();

        return redirect()
            ->route('proveedores.index')
            ->with('success', 'Proveedor eliminado correctamente.');
    }
}
