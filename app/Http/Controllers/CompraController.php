<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CompraController extends Controller
{
    public function create(Request $request): View
    {
        $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ]);

        $desde = $request->query('desde') ?: now()->startOfMonth()->toDateString();
        $hasta = $request->query('hasta') ?: now()->toDateString();

        $comprasRegistradas = Compra::query()
            ->whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hasta)
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();

        return view('compras.create', compact('comprasRegistradas', 'desde', 'hasta'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate($this->reglasFila('compras.*.') + [
            'compras' => ['required', 'array', 'min:1'],
        ]);

        $facturasUsadas = [];
        $erroresDuplicados = [];
        $rowsToSave = [];

        foreach ($request->input('compras', []) as $row) {
            $datos = $this->normalizarFila($row);

            if ($datos === null) {
                continue;
            }

            $factura = $datos['factura'];

            if ($factura !== '' && in_array($factura, $facturasUsadas, true)) {
                $erroresDuplicados[] = "La factura {$factura} ya existe en esta carga.";

                continue;
            }

            if ($factura !== '' && Compra::where('factura', $factura)->exists()) {
                $erroresDuplicados[] = "La factura {$factura} ya está registrada en el sistema.";

                continue;
            }

            $facturasUsadas[] = $factura;
            $rowsToSave[] = $datos;
        }

        if ($erroresDuplicados !== []) {
            return redirect()->route('compras.create')
                ->withInput()
                ->withErrors(['compras' => $erroresDuplicados[0]]);
        }

        DB::transaction(function () use ($rowsToSave) {
            foreach ($rowsToSave as $row) {
                Compra::create($row);
            }
        });

        return redirect()->route('compras.create')
            ->with('success', 'Compras guardadas correctamente.');
    }

    public function edit(Compra $compra): View
    {
        return view('compras.edit', [
            'compra' => $compra,
            'valores' => [
                'fecha' => $compra->fecha->format('Y-m-d'),
                'factura' => $compra->factura,
                'gasolina' => $this->formatInputValue((float) $compra->gasolina),
                'distribucion_gasolina' => $this->formatInputValue((float) $compra->distribucion_gasolina),
                'acpm' => $this->formatInputValue((float) $compra->acpm),
                'distribucion_acpm' => $this->formatInputValue((float) $compra->distribucion_acpm),
            ],
        ]);
    }

    public function update(Request $request, Compra $compra): RedirectResponse
    {
        $request->validate($this->reglasFila());

        $datos = $this->normalizarFila($request->only([
            'fecha', 'factura', 'gasolina', 'distribucion_gasolina', 'acpm', 'distribucion_acpm',
        ]));

        if ($datos === null) {
            return back()->withInput()->withErrors(['compra' => 'La compra no tiene galones ni valores.']);
        }

        if ($datos['factura'] !== '' && Compra::where('factura', $datos['factura'])->whereKeyNot($compra->id)->exists()) {
            return back()->withInput()->withErrors(['factura' => "La factura {$datos['factura']} ya está registrada en el sistema."]);
        }

        $compra->update($datos);

        return redirect()->route('compras.create', $this->filtroDelMes($compra))
            ->with('success', 'Compra actualizada correctamente.');
    }

    public function destroy(Compra $compra): RedirectResponse
    {
        $filtro = $this->filtroDelMes($compra);

        $compra->delete();

        return redirect()->route('compras.create', $filtro)
            ->with('success', 'Compra eliminada correctamente.');
    }

    /**
     * @return array<string, list<string>>
     */
    private function reglasFila(string $prefijo = ''): array
    {
        return [
            $prefijo.'fecha' => ['nullable', 'date'],
            $prefijo.'factura' => ['nullable', 'string', 'max:60'],
            $prefijo.'vr_total_fra' => ['nullable', 'string', 'max:40'],
            $prefijo.'gasolina' => ['nullable', 'string', 'max:40'],
            $prefijo.'distribucion_gasolina' => ['nullable', 'string', 'max:40'],
            $prefijo.'acpm' => ['nullable', 'string', 'max:40'],
            $prefijo.'distribucion_acpm' => ['nullable', 'string', 'max:40'],
            $prefijo.'total' => ['nullable', 'string', 'max:40'],
        ];
    }

    /**
     * Convierte una fila del formulario en los datos a guardar, o null si la fila no trae datos.
     *
     * @param  array<string, mixed>  $row
     * @return array{fecha: string, factura: string, vr_total_fra: float, gasolina: float, acpm: float, total: float, distribucion_gasolina: float, distribucion_acpm: float}|null
     */
    private function normalizarFila(array $row): ?array
    {
        $fecha = $row['fecha'] ?? null;
        $factura = trim((string) ($row['factura'] ?? ''));
        $gasolina = $this->parseDecimal($row['gasolina'] ?? null);
        $acpm = $this->parseDecimal($row['acpm'] ?? null);
        $distribucionGasolina = $this->parseDecimal($row['distribucion_gasolina'] ?? null);
        $distribucionAcpm = $this->parseDecimal($row['distribucion_acpm'] ?? null);
        $vrTotalFra = $this->parseDecimal($row['vr_total_fra'] ?? null);
        $total = $this->parseDecimal($row['total'] ?? null);

        if ($factura === '' && $gasolina === 0.0 && $acpm === 0.0 && $distribucionGasolina === 0.0 && $distribucionAcpm === 0.0 && $vrTotalFra === 0.0 && $total === 0.0) {
            return null;
        }

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

        return [
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

    /**
     * @return array{desde: string, hasta: string}
     */
    private function filtroDelMes(Compra $compra): array
    {
        return [
            'desde' => $compra->fecha->copy()->startOfMonth()->toDateString(),
            'hasta' => $compra->fecha->copy()->endOfMonth()->toDateString(),
        ];
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

    private function formatInputValue(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, ',', ''), '0'), ',');
    }
}
