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
            'compras.*.distribucion_gasolina' => ['nullable', 'string', 'max:40'],
            'compras.*.acpm' => ['nullable', 'string', 'max:40'],
            'compras.*.distribucion_acpm' => ['nullable', 'string', 'max:40'],
            'compras.*.total' => ['nullable', 'string', 'max:40'],
        ]);

        $facturasUsadas = [];
        $erroresDuplicados = [];

        foreach ($request->input('compras', []) as $row) {
            $fecha = $row['fecha'] ?? null;
            $factura = trim((string) ($row['factura'] ?? ''));
            $gasolina = $this->parseDecimal($row['gasolina'] ?? null);
            $acpm = $this->parseDecimal($row['acpm'] ?? null);
            $distribucionGasolina = $this->parseDecimal($row['distribucion_gasolina'] ?? null);
            $distribucionAcpm = $this->parseDecimal($row['distribucion_acpm'] ?? null);
            $vrTotalFra = $this->parseDecimal($row['vr_total_fra'] ?? null);
            $total = $this->parseDecimal($row['total'] ?? null);

            if (! $fecha && $factura === '' && $gasolina === 0.0 && $acpm === 0.0 && $distribucionGasolina === 0.0 && $distribucionAcpm === 0.0 && $vrTotalFra === 0.0 && $total === 0.0) {
                continue;
            }

            if ($factura !== '' && in_array($factura, $facturasUsadas, true)) {
                $erroresDuplicados[] = "La factura {$factura} ya existe en esta carga.";
                continue;
            }

            if ($factura !== '' && Compra::where('factura', $factura)->exists()) {
                $erroresDuplicados[] = "La factura {$factura} ya está registrada en el sistema.";
                continue;
            }

            $facturasUsadas[] = $factura;

            if ($total === 0.0) {
                $total = $gasolina + $acpm;
            }

            if ($vrTotalFra === 0.0 && $total > 0) {
                $vrTotalFra = $distribucionGasolina + $distribucionAcpm;
            }

            if ($distribucionGasolina === 0.0 && $total > 0) {
                $distribucionGasolina = $vrTotalFra > 0 ? ($vrTotalFra * $gasolina) / $total : 0;
            }

            if ($distribucionAcpm === 0.0 && $total > 0) {
                $distribucionAcpm = $vrTotalFra > 0 ? ($vrTotalFra * $acpm) / $total : 0;
            }

            $rowsToSave[] = [
                'fecha' => $fecha ?: now()->toDateString(),
                'factura' => $factura,
                'vr_total_fra' => $vrTotalFra,
                'gasolina' => $gasolina,
                'acpm' => $acpm,
                'total' => $total,
                'distribucion_gasolina' => $distribucionGasolina,
                'distribucion_acpm' => $distribucionAcpm,
            ];
        }

        if ($erroresDuplicados !== []) {
            return redirect()->route('compras.create')
                ->withInput()
                ->withErrors(['compras' => $erroresDuplicados[0]]);
        }

        DB::transaction(function () use ($rowsToSave) {
            foreach ($rowsToSave ?? [] as $row) {
                Compra::create($row);
            }
        });

        return redirect()->route('compras.create')
            ->withInput()
            ->with('success', 'Compras guardadas correctamente.');
    }

    private function parseDecimal(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        $clean = str_replace([' ', '\t', '\n', '\r'], '', (string) $value);
        $clean = str_replace('.', '', $clean);
        $clean = str_replace(',', '.', $clean);

        return is_numeric($clean) ? (float) $clean : 0.0;
    }
}
