<?php

namespace App\Http\Controllers;

use App\Models\Banco;
use Illuminate\Http\Request;

class BancoController extends Controller
{
    public function index()
    {
        $bancos = Banco::orderBy('name')->get();

        return view('bancos.index', compact('bancos'));
    }

    public function create()
    {
        return view('bancos.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:bancos,name',
        ]);

        Banco::create($validated);

        return redirect()
            ->route('bancos.index')
            ->with('success', 'Banco creado correctamente.');
    }

    public function show(Banco $banco)
    {
        return view('bancos.show', compact('banco'));
    }

    public function edit(Banco $banco)
    {
        return view('bancos.edit', compact('banco'));
    }

    public function update(Request $request, Banco $banco)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:bancos,name,'.$banco->id,
        ]);

        $banco->update($validated);

        return redirect()
            ->route('bancos.index')
            ->with('success', 'Banco actualizado correctamente.');
    }

    public function destroy(Banco $banco)
    {
        $banco->delete();

        return redirect()
            ->route('bancos.index')
            ->with('success', 'Banco eliminado correctamente.');
    }
}
