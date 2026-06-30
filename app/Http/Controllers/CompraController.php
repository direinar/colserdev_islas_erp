<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompraController extends Controller
{
    public function create()
    {
        return view('compras.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'compras' => ['required', 'array', 'min:1'],
            'compras.*.fecha' => ['nullable', 'date'],
            'compras.*.factura' => ['nullable', 'string', 'max:60'],
            'compras.*.vr_total_fra' => ['nullable', 'string', 'max:40'],
            'compras.*.gasolina' => ['nullable', 'string', 'max:40'],
            'compras.*.acpm' => ['nullable', 'string', 'max:40'],
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->input('compras', []) as $row) {
                $fecha = $row['fecha'] ?? null;
                $factura = trim((string) ($row['factura'] ?? ''));
                $vrTotalFra = $this->parseDecimal($row['vr_total_fra'] ?? null);
                $gasolina = $this->parseDecimal($row['gasolina'] ?? null);
                $acpm = $this->parseDecimal($row['acpm'] ?? null);

                if (! $fecha && $factura === '' && $vrTotalFra === 0.0 && $gasolina === 0.0 && $acpm === 0.0) {
                    continue;
                }

                $total = $gasolina + $acpm;
                $distribucionGasolina = $total > 0
                    ? ($vrTotalFra * $gasolina) / $total
                    : 0;
                $distribucionAcpm = $total > 0
                    ? ($vrTotalFra * $acpm) / $total
                    : 0;

                Compra::create([
                    'fecha' => $fecha ?: now()->toDateString(),
                    'factura' => $factura,
                    'vr_total_fra' => $vrTotalFra,
                    'gasolina' => $gasolina,
                    'acpm' => $acpm,
                    'total' => $total,
                    'distribucion_gasolina' => $distribucionGasolina,
                    'distribucion_acpm' => $distribucionAcpm,
                ]);
            }
        });

        return redirect()->route('compras.create')
            ->with('success', 'Compras guardadas correctamente.');
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
