<?php

namespace App\Http\Controllers;

use App\Models\ComprobanteContableCompra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComprobanteContableCompraController extends Controller
{
    public function create()
    {
        return view('comprobante_contable_compras.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'fecha_inicial' => ['nullable', 'date'],
            'fecha_final' => ['nullable', 'date'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.cuenta' => ['nullable', 'string', 'max:50'],
            'detalles.*.concepto' => ['nullable', 'string', 'max:150'],
            'detalles.*.tercero' => ['nullable', 'string', 'max:180'],
            'detalles.*.nit' => ['nullable', 'string', 'max:60'],
            'detalles.*.debito' => ['nullable', 'string', 'max:40'],
            'detalles.*.credito' => ['nullable', 'string', 'max:40'],
        ]);

        DB::transaction(function () use ($request) {
            $fechaInicial = $request->input('fecha_inicial');
            $fechaFinal = $request->input('fecha_final');

            foreach ($request->input('detalles', []) as $row) {
                $cuenta = trim((string) ($row['cuenta'] ?? ''));
                $concepto = trim((string) ($row['concepto'] ?? ''));
                $tercero = trim((string) ($row['tercero'] ?? ''));
                $nit = trim((string) ($row['nit'] ?? ''));
                $debito = round($this->parseDecimal($row['debito'] ?? null), 0);
                $credito = round($this->parseDecimal($row['credito'] ?? null), 0);

                if ($cuenta === '' && $concepto === '' && $tercero === '' && $nit === '' && $debito === 0.0 && $credito === 0.0) {
                    continue;
                }

                ComprobanteContableCompra::create([
                    'fecha_inicial' => $fechaInicial,
                    'fecha_final' => $fechaFinal,
                    'cuenta' => $cuenta,
                    'concepto' => $concepto,
                    'tercero' => $tercero,
                    'nit' => $nit,
                    'debito' => $debito,
                    'credito' => $credito,
                ]);
            }
        });

        return redirect()->route('comprobante-contable-compras.create')
            ->with('success', 'Comprobante contable de compras guardado correctamente.');
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
