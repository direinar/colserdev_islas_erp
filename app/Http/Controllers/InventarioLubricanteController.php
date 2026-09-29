<?php

namespace App\Http\Controllers;

use App\Models\Lubricant;
use App\Services\KardexInventarioService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventarioLubricanteController extends Controller
{
    public function create(Request $request, KardexInventarioService $kardexService): View
    {
        $productos = Lubricant::query()->orderBy('reference')->pluck('reference')->all();

        $producto = in_array($request->query('producto'), $productos, true)
            ? $request->query('producto')
            : ($productos[0] ?? null);

        return view('inventarios.lubricantes.create', [
            'productos' => $productos,
            'producto' => $producto,
            'kardex' => $producto !== null ? $kardexService->canastilla($producto) : null,
        ]);
    }
}
