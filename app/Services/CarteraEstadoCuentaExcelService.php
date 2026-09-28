<?php

namespace App\Services;

use App\Models\CarteraMovimiento;
use App\Models\Customer;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Estado de cuenta del cliente en Excel: mismas columnas y saldos que la
 * pantalla de cartera, incluidos los datos del vehículo para que el cliente
 * revise sus consumos. Los saldos negativos (anticipos) salen en rojo.
 */
class CarteraEstadoCuentaExcelService
{
    private const FORMATO_DINERO = '#,##0;[Red]-#,##0';

    private const ENCABEZADOS = [
        'PLANILLA', 'FECHA', 'FACTURA', 'PLACAS', 'PRODUCTO', 'GALONES', 'VR. UNITARIO',
        'BRUTO', 'DESCUENTO', 'CONCEPTO', 'VR. NETO CARGO', 'ABONOS', 'SALDO',
    ];

    public function __construct(private CarteraSaldoService $saldos) {}

    public function generar(Customer $customer, string $desde, string $hasta): Spreadsheet
    {
        $estado = $this->saldos->estadoDeCuenta($customer->id, $desde, $hasta);

        $libro = new Spreadsheet;
        $hoja = $libro->getActiveSheet();
        $hoja->setTitle('Estado de cuenta');

        $hoja->fromArray([
            ['ESTADO DE CUENTA'],
            ['Cliente', $customer->name],
            ['NIT / Documento', $customer->document],
            ['Periodo', Carbon::parse($desde)->format('d/m/Y').' al '.Carbon::parse($hasta)->format('d/m/Y')],
            ['Generado', now()->format('d/m/Y H:i')],
        ]);
        $hoja->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $filaEncabezado = 7;
        $hoja->fromArray(self::ENCABEZADOS, null, 'A'.$filaEncabezado);
        $hoja->getStyle("A{$filaEncabezado}:M{$filaEncabezado}")->getFont()->setBold(true);
        $hoja->getStyle("A{$filaEncabezado}:M{$filaEncabezado}")->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('CCCCFF');

        $fila = $filaEncabezado + 1;
        $hoja->fromArray([
            '', '', '', '', '', '', '', '', '',
            'SALDO ANTERIOR al '.Carbon::parse($desde)->subDay()->format('d/m/Y'),
            '', '', $estado['saldoAnterior'],
        ], null, 'A'.$fila);
        $hoja->getStyle("A{$fila}:M{$fila}")->getFont()->setBold(true);

        foreach ($estado['movimientos'] as $movimiento) {
            $fila++;
            $hoja->fromArray($this->fila($movimiento), null, 'A'.$fila);
        }

        $fila++;
        $hoja->fromArray([
            '', '', '', '', '', '', '', '', '', 'TOTALES',
            $estado['totalCargos'], $estado['totalAbonos'], $estado['saldoFinal'],
        ], null, 'A'.$fila);
        $hoja->getStyle("A{$fila}:M{$fila}")->getFont()->setBold(true);

        $primeraFila = $filaEncabezado + 1;
        $hoja->getStyle("F{$primeraFila}:F{$fila}")->getNumberFormat()->setFormatCode('#,##0.000');

        foreach (['G', 'H', 'I', 'K', 'L', 'M'] as $columna) {
            $hoja->getStyle("{$columna}{$primeraFila}:{$columna}{$fila}")->getNumberFormat()->setFormatCode(self::FORMATO_DINERO);
        }

        foreach (range('A', 'M') as $columna) {
            $hoja->getColumnDimension($columna)->setAutoSize(true);
        }

        return $libro;
    }

    /**
     * @return array<int, string|float|null>
     */
    private function fila(CarteraMovimiento $movimiento): array
    {
        $vehiculo = $movimiento->llevaDatosVehiculo();
        $numeroOVacio = fn ($valor): ?float => $vehiculo && (float) $valor != 0 ? (float) $valor : null;

        return [
            (string) $movimiento->planillas,
            $movimiento->fecha?->format('d/m/Y'),
            (string) $movimiento->factura,
            $vehiculo ? (string) $movimiento->placas : '',
            $vehiculo ? (string) $movimiento->producto : '',
            $numeroOVacio($movimiento->galones),
            $numeroOVacio($movimiento->vr_unitario),
            $numeroOVacio($movimiento->bruto),
            $numeroOVacio($movimiento->descuento),
            (string) $movimiento->concepto,
            (float) $movimiento->vr_neto_cargo,
            (float) $movimiento->abonos,
            (float) $movimiento->saldo_corrido,
        ];
    }
}
