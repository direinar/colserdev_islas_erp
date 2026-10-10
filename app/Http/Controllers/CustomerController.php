<?php

namespace App\Http\Controllers;

use App\Models\CarteraMovimiento;
use App\Models\Customer;
use App\Models\TurnoCartera;
use App\Models\TurnoRecaudo;
use App\Models\TurnoRecaudoAdmin;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::latest()->get();

        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'document' => 'required|string|max:100',
        ]);

        Customer::create($validated);

        return redirect()
            ->route('customers.index')
            ->with('success', 'Cliente creado correctamente.');
    }

    public function show(Customer $customer)
    {
        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'document' => 'required|string|max:100',
        ]);

        $customer->update($validated);

        return redirect()
            ->route('customers.index')
            ->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Customer $customer)
    {
        // Un cliente con movimientos de cartera o usado en planillas no se borra:
        // se perdería su historial y los informes dejarían de cuadrar.
        $enUso = CarteraMovimiento::query()->where('customer_id', $customer->id)->exists()
            || TurnoCartera::query()->where('cliente_id', $customer->id)->exists()
            || TurnoRecaudo::query()->where('cliente_id', $customer->id)->exists()
            || TurnoRecaudoAdmin::query()->where('responsable_id', $customer->id)->exists();

        if ($enUso) {
            return redirect()
                ->route('customers.index')
                ->with('error', 'No se puede eliminar '.$customer->name.': tiene movimientos de cartera o aparece en planillas de turno.');
        }

        $customer->delete();

        return redirect()
            ->route('customers.index')
            ->with('success', 'Cliente eliminado correctamente.');
    }
}
