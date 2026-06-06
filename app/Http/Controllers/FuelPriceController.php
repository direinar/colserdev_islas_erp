<?php

namespace App\Http\Controllers;

use App\Models\FuelPrice;
use Illuminate\Http\Request;

class FuelPriceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $fuels = FuelPrice::orderBy('effective_date', 'desc')->get();

        return view('fuel_prices.index', compact('fuels'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('fuel_prices.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'effective_date' => 'nullable|date',
            'active' => 'boolean',
        ]);

        FuelPrice::create($request->all());

        return redirect()->route('fuel-prices.index')
            ->with('success', 'Fuel price created successfully.');
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
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'effective_date' => 'nullable|date',
            'active' => 'boolean',
        ]);

        $fuelPrice->update($request->all());

        return redirect()->route('fuel-prices.index')
            ->with('success', 'Fuel price updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FuelPrice $fuelPrice)
    {
        $fuelPrice->delete();

        return redirect()->route('fuel-prices.index')
            ->with('success', 'Fuel price deleted successfully.');
    }
}
