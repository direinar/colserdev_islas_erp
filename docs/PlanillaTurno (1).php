<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Turno;
use Carbon\Carbon;

class PlanillaTurno extends Component
{
    // Header
    public string $fecha = '';
    public int $numero_turno = 1;
    public string $nombre_vendedor = '';
    public string $revisado_por = '';

    // Ventas por Surtidor (máx 6 filas)
    public array $ventas_surtidor = [];

    // Lecturas de surtidores (máx 12 mangueras)
    public array $lecturas = [];

    // Consignaciones (máx 5 filas)
    public array $consignaciones = [];

    // Cartera / Crédito directo (máx 5 filas)
    public array $cartera = [];

    // Medios de pago electrónicos
    public float $tc_datafono_1 = 0;
    public float $tc_datafono_2 = 0;
    public float $tc_datafono_3 = 0;
    public float $transferencias_bancolombia = 0;
    public float $gasolina_eds = 0;
    public float $puntos_redimidos = 0;

    // Ventas Urea y Lubricantes (máx 8 filas)
    public array $urea_lubricantes = [];

    // Recaudos y anticipos por islas (máx 4 filas)
    public array $recaudos_anticipos = [];

    // Varios (máx 4 filas)
    public array $varios = [];

    // Recaudos por administración (máx 4 filas)
    public array $recaudos_admin = [];

    // Traslados
    public float $traslado_sobrante = 0;
    public float $traslado_faltante = 0;

    public bool $guardado = false;
    public bool $guardando = false;
    public string $mensaje = '';

    protected $listeners = ['turnoGuardado'];

    public function mount(): void
    {
        $this->fecha = Carbon::today()->format('Y-m-d');
        $this->initVentasSurtidor();
        $this->initLecturas();
        $this->initConsignaciones();
        $this->initCartera();
        $this->initUreaLubricantes();
        $this->initRecaudosAnticipo();
        $this->initVarios();
        $this->initRecaudosAdmin();
    }

    private function initVentasSurtidor(): void
    {
        $surtidores = [
            ['label' => 'SURTIDOR 1  CTE',  'tipo' => 'CTE'],
            ['label' => 'SURTIDOR 1  ACPM', 'tipo' => 'ACPM'],
            ['label' => 'SURTIDOR 2  CTE',  'tipo' => 'CTE'],
            ['label' => 'SURTIDOR 2  ACPM', 'tipo' => 'ACPM'],
            ['label' => 'SURTIDOR 3  ACPM', 'tipo' => 'ACPM'],
            ['label' => 'SURTIDOR 3  CTE',  'tipo' => 'CTE'],
        ];
        foreach ($surtidores as $s) {
            $this->ventas_surtidor[] = ['surtidor' => $s['label'], 'tipo' => $s['tipo'], 'galones' => '', 'valor' => ''];
        }
    }

    private function initLecturas(): void
    {
        $mangueras = [
            ['label' => 'PLUS  O1',  'tipo' => 'CTE'],
            ['label' => 'PLUS  O2',  'tipo' => 'CTE'],
            ['label' => 'ACPM O3',   'tipo' => 'ACPM'],
            ['label' => 'ACPM O4',   'tipo' => 'ACPM'],
            ['label' => 'PLUS  O5',  'tipo' => 'CTE'],
            ['label' => 'PLUS  O6',  'tipo' => 'CTE'],
            ['label' => 'ACPM O7',   'tipo' => 'ACPM'],
            ['label' => 'ACPM O8',   'tipo' => 'ACPM'],
            ['label' => 'PLUS  O9',  'tipo' => 'CTE'],
            ['label' => 'PLUS  10',  'tipo' => 'CTE'],
            ['label' => 'ACPM 11',   'tipo' => 'ACPM'],
            ['label' => 'ACPM 12',   'tipo' => 'ACPM'],
        ];
        foreach ($mangueras as $m) {
            $this->lecturas[] = ['manguera' => $m['label'], 'tipo' => $m['tipo'], 'inicial' => '', 'final' => ''];
        }
    }

    private function initConsignaciones(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->consignaciones[] = ['numero' => '', 'valor' => '', 'descuento' => ''];
        }
    }

    private function initCartera(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->cartera[] = ['numero_factura' => '', 'cliente' => '', 'valor' => ''];
        }
    }

    private function initUreaLubricantes(): void
    {
        for ($i = 0; $i < 8; $i++) {
            $this->urea_lubricantes[] = ['cantidad' => '', 'producto' => '', 'valor_sin_iva' => '', 'iva' => ''];
        }
    }

    private function initRecaudosAnticipo(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->recaudos_anticipos[] = ['cliente' => '', 'valor' => ''];
        }
    }

    private function initVarios(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->varios[] = ['concepto' => '', 'valor' => ''];
        }
    }

    private function initRecaudosAdmin(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->recaudos_admin[] = ['banco_caja' => '', 'cliente' => '', 'valor' => ''];
        }
    }

    // ─── Cálculos ─────────────────────────────────────────────────────────────

    private function num(mixed $v): float
    {
        return (float) str_replace(['.', ','], ['', '.'], (string) $v);
    }

    public function getTotalGalonesCteProperty(): float
    {
        return collect($this->ventas_surtidor)
            ->where('tipo', 'CTE')
            ->sum(fn($r) => $this->num($r['galones']));
    }

    public function getTotalGalonesAcpmProperty(): float
    {
        return collect($this->ventas_surtidor)
            ->where('tipo', 'ACPM')
            ->sum(fn($r) => $this->num($r['galones']));
    }

    public function getTotalValorCteProperty(): float
    {
        return collect($this->ventas_surtidor)
            ->where('tipo', 'CTE')
            ->sum(fn($r) => $this->num($r['valor']));
    }

    public function getTotalValorAcpmProperty(): float
    {
        return collect($this->ventas_surtidor)
            ->where('tipo', 'ACPM')
            ->sum(fn($r) => $this->num($r['valor']));
    }

    public function getTotalVentaIapropiadaProperty(): float
    {
        return $this->totalValorCte + $this->totalValorAcpm;
    }

    // Galones por lectura electrónica
    public function getLecturaGalonesCteProperty(): float
    {
        return collect($this->lecturas)
            ->where('tipo', 'CTE')
            ->sum(fn($r) => max(0, $this->num($r['final']) - $this->num($r['inicial'])));
    }

    public function getLecturaGalonesAcpmProperty(): float
    {
        return collect($this->lecturas)
            ->where('tipo', 'ACPM')
            ->sum(fn($r) => max(0, $this->num($r['final']) - $this->num($r['inicial'])));
    }

    // Precios unitarios (calculados desde ventas surtidor)
    public function getPrecioCteProperty(): float
    {
        $gls = $this->totalGalonesCte;
        return $gls > 0 ? round($this->totalValorCte / $gls) : 0;
    }

    public function getPrecioAcpmProperty(): float
    {
        $gls = $this->totalGalonesAcpm;
        return $gls > 0 ? round($this->totalValorAcpm / $gls) : 0;
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

    // Consignaciones
    public function getTotalConsignacionesProperty(): float
    {
        return collect($this->consignaciones)->sum(fn($r) => $this->num($r['valor']));
    }

    public function getTotalDescuentosProperty(): float
    {
        return collect($this->consignaciones)->sum(fn($r) => $this->num($r['descuento']));
    }

    // Cartera
    public function getTotalCarteraProperty(): float
    {
        return collect($this->cartera)->sum(fn($r) => $this->num($r['valor']));
    }

    // TC / QR / Nequi
    public function getTotalTcProperty(): float
    {
        return $this->tc_datafono_1 + $this->tc_datafono_2 + $this->tc_datafono_3;
    }

    public function getTotalMediosPagoElectronicosProperty(): float
    {
        return $this->totalTc + $this->transferencias_bancolombia + $this->gasolina_eds + $this->puntos_redimidos;
    }

    // Urea y Lubricantes
    public function getUreaIvaProperty(): string
    {
        // IVA calculado automáticamente si el campo está vacío
        return '';
    }

    public function getTotalUreaSinIvaProperty(): float
    {
        return collect($this->urea_lubricantes)->sum(fn($r) => $this->num($r['valor_sin_iva']));
    }

    public function getTotalUreaIvaProperty(): float
    {
        return collect($this->urea_lubricantes)->sum(fn($r) => $this->num($r['iva']));
    }

    public function getTotalUreaTotalProperty(): float
    {
        return $this->totalUreaSinIva + $this->totalUreaIva;
    }

    // Recaudos y anticipos
    public function getTotalRecaudosProperty(): float
    {
        return collect($this->recaudos_anticipos)->sum(fn($r) => $this->num($r['valor']));
    }

    // Varios
    public function getTotalVariosProperty(): float
    {
        return collect($this->varios)->sum(fn($r) => $this->num($r['valor']));
    }

    // Recaudos admin
    public function getTotalRecaudosAdminProperty(): float
    {
        return collect($this->recaudos_admin)->sum(fn($r) => $this->num($r['valor']));
    }

    // ─── Resumen ──────────────────────────────────────────────────────────────

    public function getTotalVendidoProperty(): float
    {
        return $this->totalVentaIapropiada + $this->totalUreaTotalComputed;
    }

    public function getTotalUreaTotalComputedProperty(): float
    {
        return $this->totalUreaSinIva + $this->totalUreaIva;
    }

    public function getSobranteFaltanteCierresProperty(): float
    {
        return $this->totalRecibido - $this->totalVentaIapropiada;
    }

    public function getSobranteFaltanteLecturaProperty(): float
    {
        return $this->totalRecibido - $this->totalVentaLectura;
    }

    public function getSubtotalIngresosProperty(): float
    {
        return $this->totalConsignaciones
            + $this->totalTc
            + $this->puntos_redimidos
            + $this->gasolina_eds
            + $this->transferencias_bancolombia
            + $this->totalDescuentos
            + $this->totalCartera
            + $this->totalVarios;
    }

    public function getTotalRecibidoProperty(): float
    {
        return $this->subtotalIngresos + $this->totalRecaudos;
    }

    // ─── Guardar ──────────────────────────────────────────────────────────────

    public function guardar(): void
    {
        $this->validate([
            'fecha'         => 'required|date',
            'numero_turno'  => 'required|integer|min:1',
        ]);

        $this->guardando = true;

        try {
            $turno = Turno::create([
                'fecha'            => $this->fecha,
                'numero_turno'     => $this->numero_turno,
                'nombre_vendedor'  => $this->nombre_vendedor,
                'revisado_por'     => $this->revisado_por,
            ]);

            foreach ($this->ventas_surtidor as $v) {
                if ($this->num($v['galones']) > 0 || $this->num($v['valor']) > 0) {
                    $turno->ventasSurtidor()->create([
                        'surtidor'        => $v['surtidor'],
                        'tipo_combustible'=> $v['tipo'],
                        'galones'         => $this->num($v['galones']),
                        'valor'           => $this->num($v['valor']),
                    ]);
                }
            }

            foreach ($this->lecturas as $l) {
                if ($this->num($l['inicial']) > 0 || $this->num($l['final']) > 0) {
                    $turno->lecturasSurtidor()->create([
                        'manguera'         => $l['manguera'],
                        'tipo_combustible' => $l['tipo'],
                        'lectura_inicial'  => $this->num($l['inicial']),
                        'lectura_final'    => $this->num($l['final']),
                    ]);
                }
            }

            foreach ($this->consignaciones as $c) {
                if ($this->num($c['valor']) > 0) {
                    $turno->consignaciones()->create([
                        'numero'     => $c['numero'],
                        'valor'      => $this->num($c['valor']),
                        'descuento'  => $this->num($c['descuento']),
                    ]);
                }
            }

            foreach ($this->cartera as $c) {
                if ($this->num($c['valor']) > 0) {
                    $turno->carteraCredito()->create([
                        'numero_factura' => $c['numero_factura'],
                        'cliente'        => $c['cliente'],
                        'valor'          => $this->num($c['valor']),
                    ]);
                }
            }

            $turno->mediosPagoElectronicos()->create([
                'tc_datafono_1'               => $this->tc_datafono_1,
                'tc_datafono_2'               => $this->tc_datafono_2,
                'tc_datafono_3'               => $this->tc_datafono_3,
                'transferencias_bancolombia'   => $this->transferencias_bancolombia,
                'gasolina_eds'                => $this->gasolina_eds,
                'puntos_redimidos'            => $this->puntos_redimidos,
            ]);

            foreach ($this->urea_lubricantes as $u) {
                if ($this->num($u['valor_sin_iva']) > 0) {
                    $turno->ureaLubricantes()->create([
                        'cantidad'      => $this->num($u['cantidad']),
                        'producto'      => $u['producto'],
                        'valor_sin_iva' => $this->num($u['valor_sin_iva']),
                        'iva'           => $this->num($u['iva']),
                    ]);
                }
            }

            foreach ($this->recaudos_anticipos as $r) {
                if ($this->num($r['valor']) > 0) {
                    $turno->recaudosAnticipo()->create([
                        'cliente' => $r['cliente'],
                        'valor'   => $this->num($r['valor']),
                    ]);
                }
            }

            foreach ($this->varios as $v) {
                if ($this->num($v['valor']) > 0) {
                    $turno->varios()->create([
                        'concepto' => $v['concepto'],
                        'valor'    => $this->num($v['valor']),
                    ]);
                }
            }

            foreach ($this->recaudos_admin as $r) {
                if ($this->num($r['valor']) > 0) {
                    $turno->recaudosAdmin()->create([
                        'banco_caja' => $r['banco_caja'],
                        'cliente'    => $r['cliente'],
                        'valor'      => $this->num($r['valor']),
                    ]);
                }
            }

            $this->guardado = true;
            $this->mensaje = 'Turno #' . $this->numero_turno . ' guardado correctamente.';
        } catch (\Exception $e) {
            $this->mensaje = 'Error al guardar: ' . $e->getMessage();
        }

        $this->guardando = false;
    }

    public function nuevoTurno(): void
    {
        $this->reset();
        $this->mount();
    }

    public function render()
    {
        return view('livewire.planilla-turno')
            ->layout('layouts.app', ['title' => 'Planilla de Turnos']);
    }
}
