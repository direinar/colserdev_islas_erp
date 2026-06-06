<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Lubricant;
use Illuminate\Http\Request;

class TurnoController extends Controller
{
    public function create()
    {
        $lubricants = Lubricant::orderBy('reference')->get();
        $customers = Customer::orderBy('name')->get();

        return view('planillas.turnos.create', compact('lubricants', 'customers'));
    }
}
