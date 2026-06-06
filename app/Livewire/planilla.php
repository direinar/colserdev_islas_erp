<?php

namespace App\Livewire;

use App\Models\Planilla as PlanillaModel;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Planilla extends Component
{
    public ?int $planillaId = null;

    public string $turno = 'Mañana';

    public string $fecha;

    public string $islero = '';

    public array $surtidores = [];

    public array $precios = [];

    public array $mediosPago = [];

    public array $cartera = [];

    public array $resumenIngresos = [];

    public float $recibosYAnticipos = 0;

    public function rules(): array
    {
        return [
            'fecha' => ['required', 'date'],
            'turno' => ['required', 'string', 'max:50'],
            'islero' => ['required', 'string', 'max:120'],
            'surtidores' => ['required', 'array', 'min:1'],
            'precios' => ['required', 'array'],
            'precios.CORRIENTE' => ['required', 'numeric', 'min:0'],
            'precios.ACPM' => ['required', 'numeric', 'min:0'],
            'mediosPago' => ['required', 'array', 'min:1'],
            'cartera' => ['required', 'array', 'min:1'],
        ];
    }

    public function mount(): void
    {
        $this->fecha = now()->format('Y-m-d');

        $this->surtidores = [
            ['nombre' => 'SURTIDOR 1 CTE', 'producto' => 'CORRIENTE', 'galones' => 0],
            ['nombre' => 'SURTIDOR 1 ACPM', 'producto' => 'ACPM', 'galones' => 0],
            ['nombre' => 'SURTIDOR 2 CTE', 'producto' => 'CORRIENTE', 'galones' => 0],
            ['nombre' => 'SURTIDOR 2 ACPM', 'producto' => 'ACPM', 'galones' => 0],
            ['nombre' => 'SURTIDOR 3 ACPM', 'producto' => 'ACPM', 'galones' => 0],
            ['nombre' => 'SURTIDOR 3 CTE', 'producto' => 'CORRIENTE', 'galones' => 0],
        ];

        $this->precios = [
            'CORRIENTE' => 16555,
            'ACPM' => 11385,
        ];

        $this->mediosPago = [
            ['medio' => 'Efectivo', 'valor' => 0],
            ['medio' => 'Transferencia', 'valor' => 0],
            ['medio' => 'Datáfono', 'valor' => 0],
            ['medio' => 'Cartera', 'valor' => 0],
        ];

        $this->cartera = [
            ['cliente' => '', 'valor' => 0, 'abono_1' => 0, 'abono_2' => 0],
        ];

        $this->resumenIngresos = [
            ['concepto' => 'CONSIGNACIONES', 'valor' => 0],
            ['concepto' => 'TARJETAS DE CREDITO', 'valor' => 0],
            ['concepto' => 'REDEBAN - WIZEO', 'valor' => 0],
            ['concepto' => 'QR,DAVIPLATA, NEQUI,DAVIPLATA', 'valor' => 0],
            ['concepto' => 'FLOTAS CHEVRON', 'valor' => 0],
            ['concepto' => 'CARTERA', 'valor' => 0],
            ['concepto' => 'DESCUENTOS', 'valor' => 0],
            ['concepto' => 'PUNTOS REDIMIDOS', 'valor' => 0],
            ['concepto' => 'GASOLINA EDS', 'valor' => 0],
            ['concepto' => 'VARIOS', 'valor' => 0],
            ['concepto' => 'SOBRANTE O FALTANTE', 'valor' => 0],
        ];
    }

    public function getTotalGalonesProperty(): float
    {
        return collect($this->surtidores)
            ->sum(fn (array $surtidor): float => max((float) ($surtidor['galones'] ?? 0), 0));
    }

    public function getTotalVentasProperty(): float
    {
        return collect($this->surtidores)
            ->sum(fn (array $surtidor): float => $this->calcularVentasSurtidor($surtidor));
    }

    public function getTotalGalonesCorrienteProperty(): float
    {
        return collect($this->surtidores)
            ->filter(fn (array $surtidor): bool => ($surtidor['producto'] ?? '') === 'CORRIENTE')
            ->sum(fn (array $surtidor): float => max((float) ($surtidor['galones'] ?? 0), 0));
    }

    public function getTotalGalonesAcpmProperty(): float
    {
        return collect($this->surtidores)
            ->filter(fn (array $surtidor): bool => ($surtidor['producto'] ?? '') === 'ACPM')
            ->sum(fn (array $surtidor): float => max((float) ($surtidor['galones'] ?? 0), 0));
    }

    public function getTotalVentaCorrienteProperty(): float
    {
        return $this->totalGalonesCorriente * $this->precioPorProducto('CORRIENTE');
    }

    public function getTotalVentaAcpmProperty(): float
    {
        return $this->totalGalonesAcpm * $this->precioPorProducto('ACPM');
    }

    public function getVentaSegunCortesIapropiadaProperty(): float
    {
        return $this->totalVentaCorriente + $this->totalVentaAcpm;
    }

    public function getDiferenciaVentaSegunCortesIapropiadaProperty(): float
    {
        return $this->ventaSegunCortesIapropiada - $this->totalVentas;
    }

    public function getTotalRecaudosProperty(): float
    {
        return collect($this->mediosPago)
            ->sum(fn (array $medio): float => (float) ($medio['valor'] ?? 0));
    }

    public function getTotalCarteraProperty(): float
    {
        return collect($this->cartera)
            ->sum(fn (array $registro): float => max((float) ($registro['valor'] ?? 0) - (float) ($registro['abono_1'] ?? 0) - (float) ($registro['abono_2'] ?? 0), 0));
    }

    public function getCuadreProperty(): float
    {
        return $this->totalRecaudos + $this->totalCartera - $this->totalVentas;
    }

    public function getSubtotalIngresosProperty(): float
    {
        return collect($this->resumenIngresos)
            ->sum(fn (array $registro): float => (float) ($registro['valor'] ?? 0));
    }

    public function getVentaSegunPlanillaProperty(): float
    {
        return $this->subtotalIngresos - $this->recibosYAnticipos;
    }

    public function getSobranteFaltanteSegunCierresProperty(): float
    {
        return $this->ventaSegunPlanilla - $this->ventaSegunCortesIapropiada;
    }

    public function getSobranteFaltanteLecturaSurtidoresProperty(): float
    {
        return $this->ventaSegunPlanilla - $this->totalVentas;
    }

    public function addClienteCartera(): void
    {
        $this->cartera[] = ['cliente' => '', 'valor' => 0, 'abono_1' => 0, 'abono_2' => 0];
    }

    public function removeClienteCartera(int $index): void
    {
        unset($this->cartera[$index]);
        $this->cartera = array_values($this->cartera);
    }

    public function save(): void
    {
        $this->validate();

        DB::transaction(function (): void {
            $planilla = $this->planillaId
                ? PlanillaModel::findOrFail($this->planillaId)
                : new PlanillaModel();

            $planilla->fill([
                'fecha' => $this->fecha,
                'turno' => $this->turno,
                'islero' => $this->islero,
                'surtidores' => $this->surtidores,
                'medios_pago' => $this->mediosPago,
                'cartera' => $this->cartera,
                'total_galones' => $this->totalGalones,
                'total_ventas' => $this->totalVentas,
                'total_recaudos' => $this->totalRecaudos,
                'total_cartera' => $this->totalCartera,
                'cuadre' => $this->cuadre,
                'estado' => 'borrador',
            ]);

            $planilla->save();

            $planilla->surtidores()->delete();
            $planilla->mediosPagos()->delete();
            $planilla->carteraItems()->delete();

            $planilla->surtidores()->createMany(
                collect($this->surtidores)->map(function (array $surtidor, int $index): array {
                    $galones = max((float) ($surtidor['galones'] ?? 0), 0);
                    $producto = (string) ($surtidor['producto'] ?? '');
                    $precio = $this->precioPorProducto($producto);

                    return [
                        'nombre' => (string) ($surtidor['nombre'] ?? 'Surtidor ' . ($index + 1)),
                        'producto' => $producto,
                        'lectura_inicial' => 0,
                        'lectura_final' => $galones,
                        'precio' => $precio,
                        'galones' => $galones,
                        'venta' => $galones * $precio,
                        'orden' => $index + 1,
                    ];
                })->all(),
            );

            $planilla->mediosPagos()->createMany(
                collect($this->mediosPago)->map(function (array $medioPago, int $index): array {
                    return [
                        'medio' => (string) ($medioPago['medio'] ?? 'Medio ' . ($index + 1)),
                        'valor' => (float) ($medioPago['valor'] ?? 0),
                        'orden' => $index + 1,
                    ];
                })->all(),
            );

            $planilla->carteraItems()->createMany(
                collect($this->cartera)->map(function (array $registro, int $index): array {
                    $valor = (float) ($registro['valor'] ?? 0);
                    $abono1 = (float) ($registro['abono_1'] ?? 0);
                    $abono2 = (float) ($registro['abono_2'] ?? 0);

                    return [
                        'cliente' => (string) ($registro['cliente'] ?? ''),
                        'valor' => $valor,
                        'abono_1' => $abono1,
                        'abono_2' => $abono2,
                        'saldo' => max($valor - $abono1 - $abono2, 0),
                        'orden' => $index + 1,
                    ];
                })->all(),
            );

            $this->planillaId = $planilla->id;
        });

        session()->flash('success', 'Planilla guardada correctamente.');
    }

    protected function calcularVentasSurtidor(array $surtidor): float
    {
        $galones = max((float) ($surtidor['galones'] ?? 0), 0);
        $precio = $this->precioPorProducto((string) ($surtidor['producto'] ?? ''));

        return $galones * $precio;
    }

    protected function precioPorProducto(string $producto): float
    {
        return (float) ($this->precios[$producto] ?? 0);
    }

    public function updated($name, $value): void
    {
        // Auto-formatear galones cuando se escriben
        if (str_contains($name, 'surtidores') && str_contains($name, 'galones')) {
            // Limpiar el valor: remover caracteres no numéricos excepto punto y coma
            $cleanValue = preg_replace('/[^\d.,]/', '', (string)$value);
            // Convertir coma a punto para parseo correcto (formato decimal colombiano)
            $cleanValue = str_replace(',', '.', $cleanValue);

            // Actualizar el valor limpio
            data_set($this, $name, $cleanValue ? (float)$cleanValue : 0);
        }

        // Auto-formatear precios cuando se escriben
        if (str_contains($name, 'precios')) {
            $cleanValue = preg_replace('/[^\d.,]/', '', (string)$value);
            // Convertir coma a punto para parseo correcto (formato decimal colombiano)
            $cleanValue = str_replace(',', '.', $cleanValue);
            data_set($this, $name, $cleanValue ? (float)$cleanValue : 0);
        }
    }

    public function render()
    {
        return view('livewire.planilla');
    }
}
