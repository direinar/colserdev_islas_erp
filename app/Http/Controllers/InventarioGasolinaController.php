<?php

namespace App\Http\Controllers;

use App\Services\KardexInventarioService;
use Illuminate\View\View;

class InventarioGasolinaController extends Controller
{
    public function create(KardexInventarioService $kardexService): View
    {
        return view('inventarios.gasolina.create', ['kardex' => $kardexService->gasolina()]);
    }
}
