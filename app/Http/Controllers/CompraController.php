<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\Proveedor;
use App\Services\LiquidacionCompraCombustibleService;
use App\Support\DescargaExcel;
use App\Support\TablaExcel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompraController extends Controller
{
    public function __construct(private LiquidacionCompraCombustibleService $liquidacion) {}

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
            ->with('proveedor')
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();
        $proveedores = Proveedor::orderBy('name')->get();

        return view('compras.create', compact('comprasRegistradas', 'proveedores', 'desde', 'hasta'));
    }

    /**
     * Compras registradas del rango en Excel.
     */
    public function exportar(Request $request): StreamedResponse
    {
        $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ]);

        $desde = $request->query('desde') ?: now()->startOfMonth()->toDateString();
        $hasta = $request->query('hasta') ?: now()->toDateString();

        $compras = Compra::query()
            ->whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hasta)
            ->with('proveedor')
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();

        // Por combustible, las mismas columnas de la pantalla (plantilla con IVA).
        $columnas = ['' => 'GALONES', 'base19_' => 'BASE 19%', 'base5_' => 'BASE 5%', 'iva_' => 'LIQUIDACION IVA',
            'costo_producto_' => 'COSTO PRODUCTO', 'sobretasa_' => 'SOBRETASA', 'soldicom_' => 'SOLDICOM', 'distribucion_' => 'COSTO'];
        $encabezados = ['FECHA', 'No. FACTURA', 'PROVEEDOR'];
        foreach (Compra::COMBUSTIBLES as $combustible) {
            $nombre = strtoupper($combustible);
            foreach ($columnas as $titulo) {
                $encabezados[] = $nombre.' '.($titulo === 'COSTO' ? 'COSTO '.$nombre : $titulo);
            }
            $encabezados[] = $nombre.' COSTO UNITARIO';
        }
        array_push($encabezados, 'TOTAL VALOR', 'TOTAL GALONES');

        $filas = $compras->map(function (Compra $compra) use ($columnas): array {
            $fila = [$compra->fecha->format('d/m/Y'), (string) $compra->factura, (string) $compra->proveedor?->name];
            foreach (Compra::COMBUSTIBLES as $combustible) {
                foreach (array_keys($columnas) as $prefijo) {
                    $fila[] = (float) $compra->{$prefijo.$combustible};
                }
                $fila[] = round($compra->costoUnitario($combustible), 2);
            }

            return [...$fila, (float) $compra->vr_total_fra, (float) $compra->total];
        });

        $totales = ['TOTALES', null, null];
        foreach (Compra::COMBUSTIBLES as $combustible) {
            foreach (array_keys($columnas) as $prefijo) {
                $totales[] = (float) $compras->sum($prefijo.$combustible);
            }
            $totales[] = null;
        }
        array_push($totales, (float) $compras->sum('vr_total_fra'), (float) $compras->sum('total'));

        return DescargaExcel::respuesta(
            TablaExcel::generar(
                'Compras de combustible',
                'DESDE '.Carbon::parse($desde)->format('d/m/Y').'  HASTA '.Carbon::parse($hasta)->format('d/m/Y'),
                $encabezados,
                $filas,
                [0, 1, 2],
                $totales,
            ),
            'compras-combustible-'.$desde.'-a-'.$hasta.'.xlsx',
        );
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
        $valores = [
            'fecha' => $compra->fecha->format('Y-m-d'),
            'factura' => $compra->factura,
            'proveedor_id' => $compra->proveedor_id,
        ];
        foreach (array_slice($this->camposDigitados(), 3) as $campo) {
            $valores[$campo] = $this->formatInputValue((float) $compra->{$campo});
        }

        return view('compras.edit', [
            'compra' => $compra,
            'proveedores' => Proveedor::orderBy('name')->get(),
            'valores' => $valores,
        ]);
    }

    public function update(Request $request, Compra $compra): RedirectResponse
    {
        $request->validate($this->reglasFila());

        $datos = $this->normalizarFila($request->only($this->camposDigitados()));

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
     * Campos que se digitan en cada compra (fecha, factura, proveedor y, por combustible,
     * galones y los valores de la liquidación). Lo demás lo calcula el servidor.
     *
     * @return list<string>
     */
    private function camposDigitados(): array
    {
        $campos = ['fecha', 'factura', 'proveedor_id'];

        foreach (Compra::COMBUSTIBLES as $combustible) {
            $campos[] = $combustible;
            foreach (LiquidacionCompraCombustibleService::CAMPOS_DIGITADOS as $campo) {
                $campos[] = $campo.'_'.$combustible;
            }
        }

        return $campos;
    }

    /**
     * @return array<string, list<string>>
     */
    private function reglasFila(string $prefijo = ''): array
    {
        $reglas = [
            $prefijo.'fecha' => ['nullable', 'date'],
            $prefijo.'factura' => ['nullable', 'string', 'max:60'],
            $prefijo.'proveedor_id' => ['nullable', 'integer', 'exists:proveedores,id'],
        ];

        foreach (array_slice($this->camposDigitados(), 3) as $campo) {
            $reglas[$prefijo.$campo] = ['nullable', 'string', 'max:40'];
        }

        return $reglas;
    }

    /**
     * Convierte una fila del formulario en los datos a guardar, o null si la fila no trae datos.
     * Las fórmulas (IVA, costo, totales) se calculan aquí; nunca se toman del navegador.
     * El proveedor solo no hace que una fila cuente como compra.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>|null
     */
    private function normalizarFila(array $row): ?array
    {
        $factura = trim((string) ($row['factura'] ?? ''));
        $liquidacion = $this->liquidacion->liquidar($row);

        if ($factura === '' && $this->liquidacion->vacia($liquidacion)) {
            return null;
        }

        return [
            'fecha' => ($row['fecha'] ?? null) ?: now()->toDateString(),
            'factura' => $factura,
            'proveedor_id' => ($row['proveedor_id'] ?? null) ? (int) $row['proveedor_id'] : null,
        ] + $liquidacion;
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

    private function formatInputValue(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, ',', ''), '0'), ',');
    }
}
