<?php

namespace App\Http\Controllers;

use App\Models\InventarioAditivoMotos;
use App\Models\InventarioLubricante;
use App\Models\InventarioUreaAutomotriz;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InventarioLubricanteController extends Controller
{
    public function create()
    {
        return $this->renderForm(
            '1 - MOBIL SUPER 20W50',
            'MOBIL SUPER 20W50',
            'inventarios-lubricantes.store'
        );
    }

    public function createAditivoMotos()
    {
        return $this->renderForm(
            '2 - ADITIVO MOTOS',
            'ADITIVO MOTOS',
            'inventarios-aditivo-motos.store'
        );
    }

    public function createUreaAutomotriz()
    {
        return $this->renderForm(
            '3 - UREA AUTOMOTRIZ',
            'UREA AUTOMOTRIZ',
            'inventarios-urea-automotriz.store'
        );
    }

    public function store(Request $request)
    {
        return $this->handleStore($request, 'inventarios-lubricantes.create', InventarioLubricante::class);
    }

    public function storeAditivoMotos(Request $request)
    {
        return $this->handleStore($request, 'inventarios-aditivo-motos.create', InventarioAditivoMotos::class);
    }

    public function storeUreaAutomotriz(Request $request)
    {
        return $this->handleStore($request, 'inventarios-urea-automotriz.create', InventarioUreaAutomotriz::class);
    }

    private function renderForm(string $productoTitulo, string $productoNombre, string $formRoute)
    {
        $proveedores = Proveedor::orderBy('name', 'asc')->get();

        return view('inventarios.lubricantes.create', [
            'proveedores' => $proveedores,
            'productoTitulo' => $productoTitulo,
            'productoNombre' => $productoNombre,
            'formRoute' => $formRoute,
        ]);
    }

    private function handleStore(Request $request, string $redirectRoute, string $modelClass)
    {
        $model = new $modelClass();
        $table = $model->getTable();
        if (! Schema::hasTable($table)) {
            return back()->withErrors([
                'database' => "No existe la tabla {$table}. Ejecute php artisan migrate.",
            ])->withInput();
        }

        $request->validate([
            'producto' => ['required', 'string', 'max:120'],
            'saldo_anterior_unidades' => ['nullable', 'string', 'max:40'],
            'saldo_anterior_valor' => ['nullable', 'string', 'max:40'],
            'saldo_anterior_promedio' => ['nullable', 'string', 'max:40'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.fecha' => ['nullable', 'date'],
            'rows.*.planilla_no' => ['nullable', 'integer', 'min:1'],
            'rows.*.fc_no' => ['nullable', 'string', 'max:80'],
            'rows.*.proveedor_id' => ['nullable', 'exists:proveedores,id'],
            'rows.*.proveedor' => ['nullable', 'string', 'max:160'],
            'rows.*.entradas_unidades' => ['nullable', 'string', 'max:40'],
            'rows.*.salidas_unidades' => ['nullable', 'string', 'max:40'],
            'rows.*.valor_entradas' => ['nullable', 'string', 'max:40'],
            'rows.*.precio_venta' => ['nullable', 'string', 'max:40'],
        ]);

        DB::transaction(function () use ($request, $modelClass) {
            $producto = trim((string) $request->input('producto', 'MOBIL SUPER 20W50'));
            $saldoUnidades = $this->parseDecimal($request->input('saldo_anterior_unidades'));
            $valorSaldo = $this->parseDecimal($request->input('saldo_anterior_valor'));
            $promedio = $this->parseDecimal($request->input('saldo_anterior_promedio'));

            if ($promedio <= 0 && $saldoUnidades > 0) {
                $promedio = $valorSaldo / $saldoUnidades;
            }

            foreach ($request->input('rows', []) as $row) {
                $fecha = $row['fecha'] ?? null;
                $planillaNo = isset($row['planilla_no']) && $row['planilla_no'] !== ''
                    ? (int) $row['planilla_no']
                    : null;
                $fcNo = trim((string) ($row['fc_no'] ?? ''));
                $proveedorId = isset($row['proveedor_id']) && $row['proveedor_id'] !== ''
                    ? (int) $row['proveedor_id']
                    : null;
                $proveedor = trim((string) ($row['proveedor'] ?? ''));
                if ($proveedorId) {
                    $proveedor = trim((string) (Proveedor::query()->whereKey($proveedorId)->value('name') ?? $proveedor));
                }
                $entradasUnidades = $this->parseDecimal($row['entradas_unidades'] ?? null);
                $salidasUnidades = $this->parseDecimal($row['salidas_unidades'] ?? null);
                $valorEntradas = $this->parseDecimal($row['valor_entradas'] ?? null);
                $precioVenta = $this->parseDecimal($row['precio_venta'] ?? null);

                $hasMovementData = $planillaNo !== null
                    || $fcNo !== ''
                    || $proveedor !== ''
                    || $entradasUnidades > 0
                    || $salidasUnidades > 0
                    || $valorEntradas > 0
                    || $precioVenta > 0;

                if (! $hasMovementData) {
                    continue;
                }

                $saldoAnteriorUnidades = $saldoUnidades;
                $saldoAnteriorValor = $valorSaldo;
                $saldoAnteriorPromedio = $promedio;

                $valorSalidas = $salidasUnidades * $saldoAnteriorPromedio;
                $saldoUnidades = $saldoAnteriorUnidades + $entradasUnidades - $salidasUnidades;
                $valorSaldo = $saldoAnteriorValor + $valorEntradas - $valorSalidas;
                $promedio = $saldoUnidades > 0 ? $valorSaldo / $saldoUnidades : 0;
                $vrVenta = $salidasUnidades * $precioVenta;

                $modelClass::create([
                    'producto' => $producto,
                    'fecha' => $fecha ?: now()->toDateString(),
                    'planilla_no' => $planillaNo,
                    'fc_no' => $fcNo,
                    'proveedor' => $proveedor,
                    'entradas_unidades' => $entradasUnidades,
                    'salidas_unidades' => $salidasUnidades,
                    'saldo_unidades' => $saldoUnidades,
                    'valor_entradas' => $valorEntradas,
                    'valor_salidas' => $valorSalidas,
                    'valor_saldo' => $valorSaldo,
                    'costo_promedio' => $promedio,
                    'vr_venta' => $vrVenta,
                    'precio_venta' => $precioVenta,
                    'saldo_anterior_unidades' => $saldoAnteriorUnidades,
                    'saldo_anterior_valor' => $saldoAnteriorValor,
                    'saldo_anterior_promedio' => $saldoAnteriorPromedio,
                ]);
            }
        });

        return redirect()->route($redirectRoute)
            ->with('success', 'Inventario de lubricantes guardado correctamente.');
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
