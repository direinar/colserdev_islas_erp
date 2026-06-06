<?php

namespace App\Http\Controllers;

use App\Models\Lubricant;
use Illuminate\Http\Request;

class LubricantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
         $lubricants = Lubricant::latest()->get();

        return view('lubricants.index', compact('lubricants'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('lubricants.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([

            'reference' => 'required',
            'sale_price' => 'required|numeric',
            'iva' => 'required|numeric',
            'cost_price' => 'required|numeric',

        ]);

        $validated['total'] =
            $validated['sale_price'] +
            $validated['iva'];

        Lubricant::create($validated + [

            'supplier' => $request->supplier,
            'active' => $request->active ?? 1

        ]);

        return redirect()
            ->route('lubricants.index')
            ->with('success', 'Lubricante creado');
    }

    /**
     * Display the specified resource.
     */
    public function show(Lubricant $lubricant)
    {
        return view('lubricants.show', compact('lubricant'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Lubricant $lubricant)
    {
        return view('lubricants.edit', compact('lubricant'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Lubricant $lubricant)
    {
        $validated = $request->validate([

            'reference' => 'required',
            'sale_price' => 'required|numeric',
            'iva' => 'required|numeric',
            'cost_price' => 'required|numeric',

        ]);

        $validated['total'] =
            $validated['sale_price'] +
            $validated['iva'];

        $lubricant->update($validated + [

            'supplier' => $request->supplier,
            'active' => $request->active ?? 1

        ]);

        return redirect()
            ->route('lubricants.index')
            ->with('success', 'Lubricante actualizado');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Lubricant $lubricant)
{
    $lubricant->delete();

    return redirect()
        ->route('lubricants.index')
        ->with('success', 'Eliminado correctamente');
}
}
