<?php

namespace App\Http\Controllers;

use App\Models\CompraLubricante;
use App\Models\Lubricant;
use App\Models\Proveedor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CompraLubricanteController extends Controller
{
    public function create(Request $request): View
    {
        $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ]);

        $desde = $request->query('desde') ?: now()->startOfMonth()->toDateString();
        $hasta = $request->query('hasta') ?: now()->toDateString();

        $proveedores = Proveedor::orderBy('name', 'asc')->get();
        $productos = Lubricant::query()->orderBy('reference')->pluck('reference');

        $comprasRegistradas = CompraLubricante::query()
            ->with('proveedor')
            ->whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hasta)
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();

        return view('compras_lubricantes.create', compact('proveedores', 'productos', 'comprasRegistradas', 'desde', 'hasta'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.fecha' => ['nullable', 'date'],
            'detalles.*.proveedor_id' => ['nullable', 'exists:proveedores,id'],
            'detalles.*.producto' => ['nullable', 'string', 'exists:lubricants,reference'],
            'detalles.*.no_fc' => ['nullable', 'string', 'max:40'],
            'detalles.*.unidades' => ['nullable', 'string', 'max:40'],
            'detalles.*.valor_unitario' => ['nullable', 'string', 'max:40'],
            'detalles.*.iva' => ['nullable', 'string', 'max:40'],
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->input('detalles', []) as $row) {
                $fecha = $row['fecha'] ?? null;
                $proveedorId = $row['proveedor_id'] ?? null;
                $producto = trim((string) ($row['producto'] ?? ''));
                $noFc = trim((string) ($row['no_fc'] ?? ''));
                $unidades = $this->parseDecimal($row['unidades'] ?? null);
                $valorUnitario = $this->parseDecimal($row['valor_unitario'] ?? null);
                $iva = $this->parseDecimal($row['iva'] ?? null);

                if (! $proveedorId && $producto === '' && $noFc === '' && $unidades === 0.0 && $valorUnitario === 0.0 && $iva === 0.0) {
                    continue;
                }

                $vrSinIva = round($unidades * $valorUnitario, 0);
                $total = $vrSinIva + round($iva, 0);

                CompraLubricante::create([
                    'fecha' => $fecha ?: now()->toDateString(),
                    'proveedor_id' => $proveedorId,
                    'nombre' => $producto,
                    'no_fc' => $noFc,
                    'unidades' => $unidades,
                    'valor_unitario' => $valorUnitario,
                    'vr_sin_iva' => $vrSinIva,
                    'iva' => round($iva, 0),
                    'total' => $total,
                ]);
            }
        });

        return redirect()->route('compras-lubricantes.create')
            ->with('success', 'Compras de lubricantes guardadas correctamente.');
    }

    public function destroy(CompraLubricante $compraLubricante): RedirectResponse
    {
        $filtro = [
            'desde' => $compraLubricante->fecha->copy()->startOfMonth()->toDateString(),
            'hasta' => $compraLubricante->fecha->copy()->endOfMonth()->toDateString(),
        ];

        $compraLubricante->delete();

        return redirect()->route('compras-lubricantes.create', $filtro)
            ->with('success', 'Compra de lubricantes eliminada correctamente.');
    }

    private function parseDecimal(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        $clean = str_replace(['.', ' '], ['', ''], (string) $value);
        $clean = str_replace(',', '.', $clean);

        return is_numeric($clean) ? (float) $clean : 0.0;
    }
}
