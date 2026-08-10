<?php

namespace App\Http\Controllers;

use App\Models\InventarioAcpm;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InventarioAcpmController extends Controller
{
    public function create()
    {
        $proveedores = Proveedor::orderBy('name', 'asc')->get();

        return view('inventarios.acpm.create', compact('proveedores'));
    }

    public function store(Request $request)
    {
        $table = (new InventarioAcpm)->getTable();
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
            'rows.*.proveedor_id' => ['nullable', 'exists:proveedores,id'],
            'rows.*.proveedor' => ['nullable', 'string', 'max:160'],
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
                $proveedorId = isset($row['proveedor_id']) && $row['proveedor_id'] !== ''
                    ? (int) $row['proveedor_id']
                    : null;
                $proveedor = trim((string) ($row['proveedor'] ?? ''));
                if ($proveedorId) {
                    $proveedor = trim((string) (Proveedor::query()->whereKey($proveedorId)->value('name') ?? $proveedor));
                }
                $entradasGalones = $this->parseDecimal($row['entradas_galones'] ?? null);
                $salidasGalones = $this->parseDecimal($row['salidas_galones'] ?? null);
                $valorEntradas = $this->parseDecimal($row['valor_entradas'] ?? null);
                $precioVenta = $this->parseDecimal($row['precio_venta'] ?? null);

                $hasMovementData = $planillaNo !== null
                    || $fcCompraNo !== ''
                    || $proveedor !== ''
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

                InventarioAcpm::create([
                    'fecha' => $fecha ?: now()->toDateString(),
                    'planilla_no' => $planillaNo,
                    'fc_compra_no' => $fcCompraNo,
                    'proveedor' => $proveedor,
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

        return redirect()->route('inventarios-acpm.create')
            ->with('success', 'Inventario de ACPM guardado correctamente.');
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
