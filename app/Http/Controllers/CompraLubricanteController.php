<?php

namespace App\Http\Controllers;

use App\Models\CompraLubricante;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompraLubricanteController extends Controller
{
    public function create()
    {
        $proveedores = Proveedor::orderBy('name', 'asc')->get();

        return view('compras_lubricantes.create', compact('proveedores'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.fecha' => ['nullable', 'date'],
            'detalles.*.proveedor_id' => ['nullable', 'exists:proveedores,id'],
            'detalles.*.no_fc' => ['nullable', 'string', 'max:40'],
            'detalles.*.unidades' => ['nullable', 'string', 'max:40'],
            'detalles.*.valor_unitario' => ['nullable', 'string', 'max:40'],
            'detalles.*.iva' => ['nullable', 'string', 'max:40'],
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->input('detalles', []) as $row) {
                $fecha = $row['fecha'] ?? null;
                $proveedorId = $row['proveedor_id'] ?? null;
                $noFc = trim((string) ($row['no_fc'] ?? ''));
                $unidades = $this->parseDecimal($row['unidades'] ?? null);
                $valorUnitario = $this->parseDecimal($row['valor_unitario'] ?? null);
                $iva = $this->parseDecimal($row['iva'] ?? null);

                if (! $fecha && ! $proveedorId && $noFc === '' && $unidades === 0.0 && $valorUnitario === 0.0 && $iva === 0.0) {
                    continue;
                }

                $vrSinIva = round($unidades * $valorUnitario, 0);
                $total = $vrSinIva + round($iva, 0);

                CompraLubricante::create([
                    'fecha' => $fecha ?: now()->toDateString(),
                    'proveedor_id' => $proveedorId,
                    'nombre' => '',
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
