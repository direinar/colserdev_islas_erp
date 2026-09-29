<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\InventarioGasolina;
use App\Models\Turno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InventarioGasolinaController extends Controller
{
    public function create()
    {
        $compras = Compra::query()
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();

        $turnos = Turno::query()
            ->orderBy('fecha')
            ->orderBy('numero_turno')
            ->get(['numero_turno', 'fecha', 'lecturas_galones_corriente']);

        $filasCompras = $compras
            ->map(function (Compra $compra) {
                $fecha = $compra->fecha?->format('Y-m-d');

                return [
                    'fecha' => $fecha,
                    'numero_turno' => null,
                    'fc_compra_no' => $compra->factura,
                    'entradas_galones' => $this->formatInputValue((float) $compra->gasolina),
                    'salidas_galones' => '',
                    'valor_entradas' => $this->formatInputValue((float) $compra->distribucion_gasolina),
                    'precio_venta' => '0',
                    'orden' => $fecha.'-0-'.sprintf('%010d', $compra->id),
                ];
            });

        $filasTurnos = $turnos
            ->map(function (Turno $turno) {
                $fecha = $turno->fecha->format('Y-m-d');

                return [
                    'fecha' => $fecha,
                    'numero_turno' => $turno->numero_turno,
                    'fc_compra_no' => '',
                    'entradas_galones' => '',
                    'salidas_galones' => $this->formatQuantityInput((float) $turno->lecturas_galones_corriente),
                    'valor_entradas' => '',
                    'precio_venta' => '0',
                    'orden' => $fecha.'-1-'.sprintf('%010d', $turno->numero_turno),
                ];
            });

        $rows = $filasCompras
            ->concat($filasTurnos)
            ->sortBy('orden')
            ->map(function (array $row) {
                unset($row['orden']);

                return $row;
            })
            ->values()
            ->all();

        return view('inventarios.gasolina.create', compact('rows'));
    }

    public function store(Request $request)
    {
        abort(403, 'El inventario de gasolina se alimenta desde Compras de combustible.');

        $table = (new InventarioGasolina)->getTable();
        if (! Schema::hasTable($table)) {
            return back()->withErrors([
                'database' => "No existe la tabla {$table}. Ejecute php artisan migrate.",
            ])->withInput();
        }

        $request->validate([
            'saldo_anterior_galones' => ['nullable', 'string', 'max:40'],
            'saldo_anterior_valor' => ['nullable', 'string', 'max:40'],
            'saldo_anterior_promedio' => ['nullable', 'string', 'max:40'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.fecha' => ['nullable', 'date'],
            'rows.*.planilla_no' => ['nullable', 'integer', 'min:1'],
            'rows.*.fc_compra_no' => ['nullable', 'string', 'max:80'],
            'rows.*.entradas_galones' => ['nullable', 'string', 'max:40'],
            'rows.*.salidas_galones' => ['nullable', 'string', 'max:40'],
            'rows.*.valor_entradas' => ['nullable', 'string', 'max:40'],
            'rows.*.precio_venta' => ['nullable', 'string', 'max:40'],
        ]);

        DB::transaction(function () use ($request) {
            $saldoGalones = $this->parseDecimal($request->input('saldo_anterior_galones'));
            $valorSaldo = $this->parseDecimal($request->input('saldo_anterior_valor'));
            $promedio = $this->parseDecimal($request->input('saldo_anterior_promedio'));

            if ($promedio <= 0 && $saldoGalones > 0) {
                $promedio = $valorSaldo / $saldoGalones;
            }

            foreach ($request->input('rows', []) as $row) {
                $fecha = $row['fecha'] ?? null;
                $planillaNo = isset($row['planilla_no']) && $row['planilla_no'] !== ''
                    ? (int) $row['planilla_no']
                    : null;
                $fcCompraNo = trim((string) ($row['fc_compra_no'] ?? ''));
                $entradasGalones = $this->parseDecimal($row['entradas_galones'] ?? null);
                $salidasGalones = $this->parseDecimal($row['salidas_galones'] ?? null);
                $valorEntradas = $this->parseDecimal($row['valor_entradas'] ?? null);
                $precioVenta = $this->parseDecimal($row['precio_venta'] ?? null);

                $hasMovementData = $planillaNo !== null
                    || $fcCompraNo !== ''
                    || $entradasGalones > 0
                    || $salidasGalones > 0
                    || $valorEntradas > 0
                    || $precioVenta > 0;

                if (! $hasMovementData) {
                    continue;
                }

                $saldoAnteriorGalones = $saldoGalones;
                $saldoAnteriorValor = $valorSaldo;
                $saldoAnteriorPromedio = $promedio;

                $valorSalidas = $salidasGalones * $saldoAnteriorPromedio;
                $saldoGalones = $saldoAnteriorGalones + $entradasGalones - $salidasGalones;
                $valorSaldo = $saldoAnteriorValor + $valorEntradas - $valorSalidas;
                $promedio = $saldoGalones > 0 ? $valorSaldo / $saldoGalones : 0;
                $vrVenta = $salidasGalones * $precioVenta;

                InventarioGasolina::create([
                    'fecha' => $fecha ?: now()->toDateString(),
                    'planilla_no' => $planillaNo,
                    'fc_compra_no' => $fcCompraNo,
                    'entradas_galones' => $entradasGalones,
                    'salidas_galones' => $salidasGalones,
                    'saldo_galones' => $saldoGalones,
                    'valor_entradas' => $valorEntradas,
                    'valor_salidas' => $valorSalidas,
                    'valor_saldo' => $valorSaldo,
                    'costo_promedio' => $promedio,
                    'vr_venta' => $vrVenta,
                    'precio_venta' => $precioVenta,
                    'saldo_anterior_galones' => $saldoAnteriorGalones,
                    'saldo_anterior_valor' => $saldoAnteriorValor,
                    'saldo_anterior_promedio' => $saldoAnteriorPromedio,
                ]);
            }
        });

        return redirect()->route('inventarios-gasolina.create')
            ->with('success', 'Inventario de gasolina guardado correctamente.');
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
        if (fmod($value, 1.0) === 0.0) {
            return (string) (int) $value;
        }

        return rtrim(rtrim((string) number_format($value, 3, '.', ''), '0'), '.');
    }

    private function formatQuantityInput(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, ',', ''), '0'), ',');
    }
}
