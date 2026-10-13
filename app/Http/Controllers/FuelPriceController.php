<?php

namespace App\Http\Controllers;

use App\Models\FuelPrice;
use App\Models\Turno;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FuelPriceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $fuels = FuelPrice::orderByDesc('turno_desde')->orderByDesc('id')->get();
        $usados = $fuels->filter(fn (FuelPrice $fuel): bool => $fuel->usadoEnPlanillas())->pluck('id');

        return view('fuel_prices.index', compact('fuels', 'usados'));
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
        if ($fuelPrice->usadoEnPlanillas()) {
            return $this->precioUsado();
        }

        return view('fuel_prices.edit', compact('fuelPrice'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FuelPrice $fuelPrice)
    {
        if ($fuelPrice->usadoEnPlanillas()) {
            return $this->precioUsado();
        }

        $fuelPrice->update($this->validar($request));

        return redirect()->route('fuel-prices.index')
            ->with('success', 'Precio de combustible actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FuelPrice $fuelPrice)
    {
        if ($fuelPrice->usadoEnPlanillas()) {
            return $this->precioUsado();
        }

        $fuelPrice->delete();

        return redirect()->route('fuel-prices.index')
            ->with('success', 'Precio de combustible eliminado correctamente.');
    }

    private function precioUsado(): RedirectResponse
    {
        return redirect()->route('fuel-prices.index')
            ->with('error', 'Este precio ya liquidó planillas guardadas y no se puede editar ni eliminar. Si el precio cambió, registre un precio nuevo desde el turno en que cambia.');
    }

    /**
     * Solo se guardan los campos validados (antes se guardaba todo lo enviado). Un precio
     * no puede regir desde un turno que ya tiene una planilla revisada en adelante: le
     * cambiaría el precio a una planilla cerrada.
     *
     * @return array{name: string, price: numeric-string|float, turno_desde: int|string, effective_date?: string|null}
     */
    private function validar(Request $request): array
    {
        $datos = $request->validate([
            'name' => 'required|string|in:Gasolina,ACPM',
            'price' => 'required|numeric|min:0',
            'turno_desde' => 'required|integer|min:1',
            'effective_date' => 'nullable|date',
        ]);

        $ultimaRevisada = (int) Turno::query()->where('revisado', true)->max('numero_turno');

        if ((int) $datos['turno_desde'] <= $ultimaRevisada) {
            throw ValidationException::withMessages([
                'turno_desde' => 'La planilla del turno '.$ultimaRevisada.' ya está revisada: el precio debe regir desde el turno '.($ultimaRevisada + 1).' o uno posterior.',
            ]);
        }

        return $datos;
    }
}
