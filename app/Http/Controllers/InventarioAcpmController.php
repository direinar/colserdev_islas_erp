<?php

namespace App\Http\Controllers;

use App\Services\KardexInventarioService;
use Illuminate\View\View;

class InventarioAcpmController extends Controller
{
    public function create(KardexInventarioService $kardexService): View
    {
        return view('inventarios.acpm.create', ['kardex' => $kardexService->acpm()]);
    }
}
