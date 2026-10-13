<?php

namespace App\Http\Controllers;

use App\Models\FuelPrice;
use App\Models\Turno;
use Illuminate\Http\Request;

class FuelPriceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $fuels = FuelPrice::orderByDesc('turno_desde')->orderByDesc('id')->get();

        return view('fuel_prices.index', compact('fuels'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Por defecto, el nuevo precio rige desde la próxima planilla que se registre.
        $siguienteTurno = (int) Turno::max('numero_turno') + 1;

        return view('fuel_prices.create', compact('siguienteTurno'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        FuelPrice::create($this->validar($request));

        return redirect()->route('fuel-prices.index')
            ->with('success', 'Precio de combustible creado correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(FuelPrice $fuelPrice)
    {
        return view('fuel_prices.show', compact('fuelPrice'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(FuelPrice $fuelPrice)
    {
        return view('fuel_prices.edit', compact('fuelPrice'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FuelPrice $fuelPrice)
    {
        $fuelPrice->update($this->validar($request));

        return redirect()->route('fuel-prices.index')
            ->with('success', 'Precio de combustible actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FuelPrice $fuelPrice)
    {
        $fuelPrice->delete();

        return redirect()->route('fuel-prices.index')
            ->with('success', 'Precio de combustible eliminado correctamente.');
    }

    /**
     * Solo se guardan los campos validados (antes se guardaba todo lo enviado).
     *
     * @return array{name: string, price: numeric-string|float, turno_desde: int|string, effective_date?: string|null, active?: bool}
     */
    private function validar(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|in:Gasolina,ACPM',
            'price' => 'required|numeric|min:0',
            'turno_desde' => 'required|integer|min:1',
            'effective_date' => 'nullable|date',
            'active' => 'boolean',
        ]);
    }
}
