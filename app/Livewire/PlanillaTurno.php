<?php

namespace App\Livewire;

use App\Models\Turno;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class PlanillaTurno extends Component
{
    public ?int $turnoId = null;

    public string $fecha = '';

    public int $numero_turno = 1;

    public string $nombre_vendedor = '';

    public string $revisado_por = '';

    public array $precios = [
        'CTE' => 16.50,
        'ACPM' => 9.85,
    ];

    public array $ventas_surtidor = [];

    public array $lecturas = [];

    public array $consignaciones = [];

    public array $cartera = [];

    public float $tc_datafono_1 = 0;

    public float $tc_datafono_2 = 0;

    public float $tc_datafono_3 = 0;

    public float $transferencias_bancolombia = 0;

    public float $gasolina_eds = 0;

    public float $puntos_redimidos = 0;

    public array $urea_lubricantes = [];

    public array $recaudos_anticipos = [];

    public array $varios = [];

    public array $recaudos_administracion = [];

    public bool $guardado = false;

    public string $mensaje = '';

    public array $catalogo_productos = [];

    public function mount(): void
    {
        $this->fecha = now()->format('Y-m-d');
        $this->catalogo_productos = $this->catalogoUreaLubricantes();

        $this->initVentasSurtidor();
        $this->initLecturas();
        $this->initConsignaciones();
        $this->initCartera();
        $this->initUreaLubricantes();
        $this->initRecaudosAnticipos();
        $this->initVarios();
        $this->initRecaudosAdministracion();
        $this->loadDefaultsFromLatestTurno();
    }

    private function initVentasSurtidor(): void
    {
        $this->ventas_surtidor = [
            ['surtidor' => 'SURTIDOR 1 CTE', 'tipo' => 'CTE', 'galones' => 0],
            ['surtidor' => 'SURTIDOR 1 ACPM', 'tipo' => 'ACPM', 'galones' => 0],
            ['surtidor' => 'SURTIDOR 2 CTE', 'tipo' => 'CTE', 'galones' => 0],
            ['surtidor' => 'SURTIDOR 2 ACPM', 'tipo' => 'ACPM', 'galones' => 0],
            ['surtidor' => 'SURTIDOR 3 ACPM', 'tipo' => 'ACPM', 'galones' => 0],
            ['surtidor' => 'SURTIDOR 3 CTE', 'tipo' => 'CTE', 'galones' => 0],
        ];
    }

    private function initLecturas(): void
    {
        $this->lecturas = [
            ['manguera' => 'PLUS O1', 'tipo' => 'CTE', 'inicial' => 0, 'final' => 0],
            ['manguera' => 'PLUS O2', 'tipo' => 'CTE', 'inicial' => 0, 'final' => 0],
            ['manguera' => 'ACPM O3', 'tipo' => 'ACPM', 'inicial' => 0, 'final' => 0],
            ['manguera' => 'ACPM O4', 'tipo' => 'ACPM', 'inicial' => 0, 'final' => 0],
            ['manguera' => 'PLUS O5', 'tipo' => 'CTE', 'inicial' => 0, 'final' => 0],
            ['manguera' => 'PLUS O6', 'tipo' => 'CTE', 'inicial' => 0, 'final' => 0],
            ['manguera' => 'ACPM O7', 'tipo' => 'ACPM', 'inicial' => 0, 'final' => 0],
            ['manguera' => 'ACPM O8', 'tipo' => 'ACPM', 'inicial' => 0, 'final' => 0],
            ['manguera' => 'PLUS O9', 'tipo' => 'CTE', 'inicial' => 0, 'final' => 0],
            ['manguera' => 'PLUS 10', 'tipo' => 'CTE', 'inicial' => 0, 'final' => 0],
            ['manguera' => 'ACPM 11', 'tipo' => 'ACPM', 'inicial' => 0, 'final' => 0],
            ['manguera' => 'ACPM 12', 'tipo' => 'ACPM', 'inicial' => 0, 'final' => 0],
        ];
    }

    private function initConsignaciones(): void
    {
        $this->consignaciones = array_fill(0, 5, [
            'numero' => '',
            'valor' => 0,
            'descuento' => 0,
        ]);
    }

    private function initCartera(): void
    {
        $this->cartera = array_fill(0, 5, [
            'numero_factura' => '',
            'cliente' => '',
            'valor' => 0,
        ]);
    }

    private function initUreaLubricantes(): void
    {
        $this->urea_lubricantes = array_fill(0, 6, [
            'cantidad' => 0,
            'producto' => '',
            'nombre_producto' => '',
            'valor_sin_iva' => 0,
            'iva' => 0,
        ]);
    }

    private function initRecaudosAnticipos(): void
    {
        $this->recaudos_anticipos = array_fill(0, 4, [
            'cliente' => '',
            'valor' => 0,
        ]);
    }

    private function initVarios(): void
    {
        $this->varios = array_fill(0, 4, [
            'concepto' => '',
            'valor' => 0,
        ]);
    }

    private function initRecaudosAdministracion(): void
    {
        $this->recaudos_administracion = array_fill(0, 4, [
            'banco_caja' => '',
            'cliente' => '',
            'valor' => 0,
        ]);
    }

    private function loadDefaultsFromLatestTurno(): void
    {
        $ultimoTurno = Turno::query()
            ->whereDate('fecha', $this->fecha)
            ->orderByDesc('numero_turno')
            ->first();

        if (! $ultimoTurno) {
            return;
        }

        $this->numero_turno = (int) $ultimoTurno->numero_turno + 1;

        if (! empty($ultimoTurno->precio_corriente)) {
            $this->precios['CTE'] = (float) $ultimoTurno->precio_corriente;
        }

        if (! empty($ultimoTurno->precio_acpm)) {
            $this->precios['ACPM'] = (float) $ultimoTurno->precio_acpm;
        }

        $lecturasAnteriores = $ultimoTurno->surtidores
            ->sortBy(fn ($surtidor) => $this->lecturaOrderIndex($surtidor->manguera))
            ->values()
            ->all();

        foreach ($this->lecturas as $index => $lectura) {
            $surtidorAnterior = $lecturasAnteriores[$index] ?? null;
            $this->lecturas[$index]['inicial'] = $this->numberValue($surtidorAnterior->lectura_final ?? 0);
        }
    }

    private function lecturaOrderIndex(string $manguera): int
    {
        $orden = [
            'PLUS O1' => 0,
            'PLUS O2' => 1,
            'ACPM O3' => 2,
            'ACPM O4' => 3,
            'PLUS O5' => 4,
            'PLUS O6' => 5,
            'ACPM O7' => 6,
            'ACPM O8' => 7,
            'PLUS O9' => 8,
            'PLUS 10' => 9,
            'ACPM 11' => 10,
            'ACPM 12' => 11,
        ];

        return $orden[$manguera] ?? PHP_INT_MAX;
    }

    private function catalogoUreaLubricantes(): array
    {
        return [
            [
                'codigo' => 'MOBIL SUPER 20W50',
                'nombre' => 'MOBIL SUPER 20W50',
                'valor_sin_iva' => 250000,
                'iva' => 47500,
            ],
            [
                'codigo' => 'ADITIVO MOTOS',
                'nombre' => 'ADITIVO MOTOS',
                'valor_sin_iva' => 14000,
                'iva' => 2800,
            ],
            [
                'codigo' => 'UREA AUTOMOTRIZ',
                'nombre' => 'UREA AUTOMOTRIZ',
                'valor_sin_iva' => 75000,
                'iva' => 0,
            ],
        ];
    }

    protected function numberValue(mixed $value): float
    {
        $normalized = trim((string) $value);

        if ($normalized === '') {
            return 0.0;
        }

        $normalized = preg_replace('/[^\d,\.\-]/', '', $normalized) ?? '';

        if ($normalized === '') {
            return 0.0;
        }

        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        } else {
            $normalized = str_replace(',', '.', $normalized);
        }

        return (float) $normalized;
    }

    protected function typeValue(string $name): float
    {
        return $this->numberValue(data_get($this, $name));
    }

    public function updated(string $name, mixed $value): void
    {
        if (preg_match('/^(tc_datafono_1|tc_datafono_2|tc_datafono_3|transferencias_bancolombia|gasolina_eds|puntos_redimidos)$/', $name) === 1) {
            data_set($this, $name, $this->numberValue($value));

            return;
        }

        if (preg_match('/^(ventas_surtidor|lecturas|consignaciones|cartera|recaudos_anticipos|varios|recaudos_administracion)\.\d+\.(galones|inicial|final|valor|descuento|cantidad)$/', $name) === 1) {
            data_set($this, $name, $this->numberValue($value));

            return;
        }

        if (preg_match('/^urea_lubricantes\.\d+\.cantidad$/', $name) === 1) {
            data_set($this, $name, $this->numberValue($value));

            return;
        }

        if (preg_match('/^urea_lubricantes\.\d+\.producto$/', $name) === 1) {
            $this->applyUreaProductSelection($name, (string) $value);
        }
    }

    private function applyUreaProductSelection(string $name, string $codigo): void
    {
        $index = (int) explode('.', $name)[1];
        $product = collect($this->catalogo_productos)->firstWhere('codigo', $codigo);

        $this->urea_lubricantes[$index]['producto'] = $codigo;
        $this->urea_lubricantes[$index]['nombre_producto'] = (string) ($product['nombre'] ?? '');
        $this->urea_lubricantes[$index]['valor_sin_iva'] = $this->numberValue($product['valor_sin_iva'] ?? 0);
        $this->urea_lubricantes[$index]['iva'] = $this->numberValue($product['iva'] ?? 0);
    }

    public function getPrecioCteProperty(): float
    {
        return $this->numberValue($this->precios['CTE'] ?? 0);
    }

    public function getPrecioAcpmProperty(): float
    {
        return $this->numberValue($this->precios['ACPM'] ?? 0);
    }

    public function getTotalGalonesCteProperty(): float
    {
        return collect($this->ventas_surtidor)
            ->where('tipo', 'CTE')
            ->sum(fn (array $row): float => $this->numberValue($row['galones'] ?? 0));
    }

    public function getTotalGalonesAcpmProperty(): float
    {
        return collect($this->ventas_surtidor)
            ->where('tipo', 'ACPM')
            ->sum(fn (array $row): float => $this->numberValue($row['galones'] ?? 0));
    }

    public function getTotalValorCteProperty(): float
    {
        return $this->totalGalonesCte * $this->precioCte;
    }

    public function getTotalValorAcpmProperty(): float
    {
        return $this->totalGalonesAcpm * $this->precioAcpm;
    }

    public function getTotalVentaIapropiadaProperty(): float
    {
        return $this->totalValorCte + $this->totalValorAcpm;
    }

    public function getLecturaGalonesCteProperty(): float
    {
        return collect($this->lecturas)
            ->where('tipo', 'CTE')
            ->sum(fn (array $row): float => max(0, $this->numberValue($row['final'] ?? 0) - $this->numberValue($row['inicial'] ?? 0)));
    }

    public function getLecturaGalonesAcpmProperty(): float
    {
        return collect($this->lecturas)
            ->where('tipo', 'ACPM')
            ->sum(fn (array $row): float => max(0, $this->numberValue($row['final'] ?? 0) - $this->numberValue($row['inicial'] ?? 0)));
    }

    public function getLecturaValorCteProperty(): float
    {
        return $this->lecturaGalonesCte * $this->precioCte;
    }

    public function getLecturaValorAcpmProperty(): float
    {
        return $this->lecturaGalonesAcpm * $this->precioAcpm;
    }

    public function getTotalVentaLecturaProperty(): float
    {
        return $this->lecturaValorCte + $this->lecturaValorAcpm;
    }

    public function getTotalConsignacionesProperty(): float
    {
        return collect($this->consignaciones)
            ->sum(fn (array $row): float => $this->numberValue($row['valor'] ?? 0));
    }

    public function getTotalDescuentosProperty(): float
    {
        return collect($this->consignaciones)
            ->sum(fn (array $row): float => $this->numberValue($row['descuento'] ?? 0));
    }

    public function getTotalCarteraProperty(): float
    {
        return collect($this->cartera)
            ->sum(fn (array $row): float => $this->numberValue($row['valor'] ?? 0));
    }

    public function getTotalTcProperty(): float
    {
        return $this->tc_datafono_1 + $this->tc_datafono_2 + $this->tc_datafono_3;
    }

    public function getTotalMediosPagoElectronicosProperty(): float
    {
        return $this->totalTc
            + $this->transferencias_bancolombia
            + $this->gasolina_eds
            + $this->puntos_redimidos;
    }

    public function getTotalUreaSinIvaProperty(): float
    {
        return collect($this->urea_lubricantes)
            ->sum(fn (array $row): float => $this->numberValue($row['valor_sin_iva'] ?? 0) * $this->numberValue($row['cantidad'] ?? 0));
    }

    public function getTotalUreaIvaProperty(): float
    {
        return collect($this->urea_lubricantes)
            ->sum(fn (array $row): float => $this->numberValue($row['iva'] ?? 0) * $this->numberValue($row['cantidad'] ?? 0));
    }

    public function getTotalUreaTotalProperty(): float
    {
        return $this->totalUreaSinIva + $this->totalUreaIva;
    }

    public function getTotalRecaudosProperty(): float
    {
        return collect($this->recaudos_anticipos)
            ->sum(fn (array $row): float => $this->numberValue($row['valor'] ?? 0));
    }

    public function getTotalVariosProperty(): float
    {
        return collect($this->varios)
            ->sum(fn (array $row): float => $this->numberValue($row['valor'] ?? 0));
    }

    public function getTotalRecaudosAdministracionProperty(): float
    {
        return collect($this->recaudos_administracion)
            ->sum(fn (array $row): float => $this->numberValue($row['valor'] ?? 0));
    }

    public function getSubtotalIngresosProperty(): float
    {
        return $this->totalConsignaciones
            + $this->totalMediosPagoElectronicos
            + $this->totalDescuentos
            + $this->totalCartera
            + $this->totalVarios;
    }

    public function getTotalRecibidoProperty(): float
    {
        return $this->subtotalIngresos + $this->totalRecaudos;
    }

    public function getTotalVendidoIapropiadaProperty(): float
    {
        return $this->totalVentaIapropiada + $this->totalUreaTotal;
    }

    public function getTotalVendidoLecturaProperty(): float
    {
        return $this->totalVentaLectura + $this->totalUreaTotal;
    }

    public function getFaltanteSobranteIapropiadaProperty(): float
    {
        return $this->totalRecibido - $this->totalVendidoIapropiada;
    }

    public function getFaltanteSobranteLecturaProperty(): float
    {
        return $this->totalRecibido - $this->totalVendidoLectura;
    }

    private function snapshotVentasSurtidor(): array
    {
        return collect($this->ventas_surtidor)->map(function (array $row): array {
            $galones = $this->numberValue($row['galones'] ?? 0);
            $tipo = (string) ($row['tipo'] ?? '');
            $precio = $tipo === 'CTE' ? $this->precioCte : $this->precioAcpm;

            return [
                'surtidor' => (string) ($row['surtidor'] ?? ''),
                'tipo' => $tipo,
                'galones' => $galones,
                'precio' => $precio,
                'valor' => $galones * $precio,
            ];
        })->all();
    }

    private function snapshotLecturas(): array
    {
        return collect($this->lecturas)->map(function (array $row): array {
            $inicial = $this->numberValue($row['inicial'] ?? 0);
            $final = $this->numberValue($row['final'] ?? 0);
            $galones = max(0, $final - $inicial);
            $tipo = (string) ($row['tipo'] ?? '');
            $precio = $tipo === 'CTE' ? $this->precioCte : $this->precioAcpm;

            return [
                'manguera' => (string) ($row['manguera'] ?? ''),
                'tipo' => $tipo,
                'lectura_inicial' => $inicial,
                'lectura_final' => $final,
                'galones' => $galones,
                'precio' => $precio,
                'valor' => $galones * $precio,
            ];
        })->all();
    }

    private function snapshotUreaLubricantes(): array
    {
        return collect($this->urea_lubricantes)->map(function (array $row): array {
            $cantidad = $this->numberValue($row['cantidad'] ?? 0);

            return [
                'cantidad' => $cantidad,
                'producto' => (string) ($row['producto'] ?? ''),
                'nombre_producto' => (string) ($row['nombre_producto'] ?? ''),
                'valor_sin_iva' => $this->numberValue($row['valor_sin_iva'] ?? 0),
                'iva' => $this->numberValue($row['iva'] ?? 0),
                'total' => $cantidad * ($this->numberValue($row['valor_sin_iva'] ?? 0) + $this->numberValue($row['iva'] ?? 0)),
            ];
        })->all();
    }

    private function snapshotRows(array $rows, array $fields): array
    {
        return collect($rows)->map(function (array $row) use ($fields): array {
            $snapshot = [];

            foreach ($fields as $field) {
                $snapshot[$field] = is_numeric($row[$field] ?? null)
                    ? $this->numberValue($row[$field] ?? 0)
                    : trim((string) ($row[$field] ?? ''));
            }

            return $snapshot;
        })->all();
    }

    public function guardar(): void
    {
        $this->validate([
            'fecha' => ['required', 'date'],
            'numero_turno' => ['required', 'integer', 'min:1'],
            'nombre_vendedor' => ['nullable', 'string', 'max:120'],
            'revisado_por' => ['nullable', 'string', 'max:120'],
        ]);

        DB::transaction(function (): void {
            $turno = $this->turnoId ? Turno::query()->findOrFail($this->turnoId) : new Turno;

            $turno->fill([
                'fecha' => $this->fecha,
                'numero_turno' => $this->numero_turno,
                'nombre_vendedor' => $this->nombre_vendedor !== '' ? $this->nombre_vendedor : null,
                'revisado_por' => $this->revisado_por !== '' ? $this->revisado_por : null,
                'precio_corriente' => $this->precioCte,
                'precio_acpm' => $this->precioAcpm,
                'ventas_surtidor' => $this->snapshotVentasSurtidor(),
                'lecturas' => $this->snapshotLecturas(),
                'consignaciones' => $this->snapshotRows($this->consignaciones, ['numero', 'valor', 'descuento']),
                'cartera' => $this->snapshotRows($this->cartera, ['numero_factura', 'cliente', 'valor']),
                'tc_datafono_1' => $this->tc_datafono_1,
                'tc_datafono_2' => $this->tc_datafono_2,
                'tc_datafono_3' => $this->tc_datafono_3,
                'transferencias_bancolombia' => $this->transferencias_bancolombia,
                'gasolina_eds' => $this->gasolina_eds,
                'puntos_redimidos' => $this->puntos_redimidos,
                'urea_lubricantes' => $this->snapshotUreaLubricantes(),
                'recaudos_anticipos' => $this->snapshotRows($this->recaudos_anticipos, ['cliente', 'valor']),
                'varios' => $this->snapshotRows($this->varios, ['concepto', 'valor']),
                'recaudos_administracion' => $this->snapshotRows($this->recaudos_administracion, ['banco_caja', 'cliente', 'valor']),
                'total_venta_iapropiada' => $this->totalVentaIapropiada,
                'total_venta_lectura' => $this->totalVentaLectura,
                'total_urea_sin_iva' => $this->totalUreaSinIva,
                'total_urea_iva' => $this->totalUreaIva,
                'subtotal_ingresos' => $this->subtotalIngresos,
                'total_recaudos' => $this->totalRecaudos,
                'total_recibido' => $this->totalRecibido,
                'faltante_sobrante_iapropiada' => $this->faltanteSobranteIapropiada,
                'faltante_sobrante_lectura' => $this->faltanteSobranteLectura,
            ]);

            $turno->save();

            $this->turnoId = $turno->id;
        });

        $this->guardado = true;
        $this->mensaje = 'Turno #'.$this->numero_turno.' guardado correctamente.';
    }

    public function nuevoTurno(): void
    {
        $this->resetErrorBag();
        $this->resetValidation();

        $this->turnoId = null;
        $this->guardado = false;
        $this->mensaje = '';
        $this->mount();
    }

    public function render()
    {
        return view('livewire.planilla-turno', [
            'precioCte' => $this->precioCte,
            'precioAcpm' => $this->precioAcpm,

            // Ventas / lecturas
            'totalGalonesCte' => $this->totalGalonesCte,
            'totalGalonesAcpm' => $this->totalGalonesAcpm,
            'totalValorCte' => $this->totalValorCte,
            'totalValorAcpm' => $this->totalValorAcpm,
            'totalVentaIapropiada' => $this->totalVentaIapropiada,

            'lecturaGalonesCte' => $this->lecturaGalonesCte,
            'lecturaGalonesAcpm' => $this->lecturaGalonesAcpm,
            'lecturaValorCte' => $this->lecturaValorCte,
            'lecturaValorAcpm' => $this->lecturaValorAcpm,
            'totalVentaLectura' => $this->totalVentaLectura,

            // Resumen / ingresos
            'totalConsignaciones' => $this->totalConsignaciones,
            'totalDescuentos' => $this->totalDescuentos,
            'totalCartera' => $this->totalCartera,
            'totalTc' => $this->totalTc,
            'totalUreaSinIva' => $this->totalUreaSinIva,
            'totalUreaIva' => $this->totalUreaIva,
            'totalUreaTotal' => $this->totalUreaTotal,
            'totalRecaudos' => $this->totalRecaudos,
            'totalVarios' => $this->totalVarios,
            'subtotalIngresos' => $this->subtotalIngresos,
            'totalRecibido' => $this->totalRecibido,
            'totalVendidoIapropiada' => $this->totalVendidoIapropiada,
            'totalVendidoLectura' => $this->totalVendidoLectura,
            'faltanteSobranteIapropiada' => $this->faltanteSobranteIapropiada,
            'faltanteSobranteLectura' => $this->faltanteSobranteLectura,
        ]);
    }
}
