<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Informes de saldos en Excel: saldos de cartera, saldos de canastilla o precios de venta
 * de canastilla, según la pestaña que se exporte.
 */
class InformeSaldosExcelService
{
    private const FORMATO_PESOS = '#,##0;[Red]-#,##0';

    public function __construct(private InformeSaldosService $saldos) {}

    public function generar(string $seccion, string $corte, bool $soloConSaldo): Spreadsheet
    {
        $libro = new Spreadsheet;
        $hoja = $libro->getActiveSheet();
        $fechaCorte = 'FECHA DE CORTE: '.Carbon::parse($corte)->format('d/m/Y');

        match ($seccion) {
            'cartera' => $this->cartera($hoja, $this->saldos->cartera($corte, $soloConSaldo), $fechaCorte),
            'canastilla' => $this->canastilla($hoja, $this->saldos->canastilla($corte), $fechaCorte),
            'precios' => $this->precios($hoja, $this->saldos->precios()),
        };

        $hoja->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('1F4E78');
        foreach (['A', 'B', 'C', 'D'] as $columna) {
            $hoja->getColumnDimension($columna)->setAutoSize(true);
        }

        return $libro;
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function cartera(Worksheet $hoja, array $datos, string $fechaCorte): void
    {
        $hoja->setTitle('Saldos de cartera');
        $hoja->setCellValue('A1', 'INFORME DE SALDOS DE CARTERA');
        $hoja->setCellValue('A2', $fechaCorte);
        $this->encabezado($hoja, ['CLIENTE', 'NIT / DOCUMENTO', 'SALDO']);

        $fila = 4;
        foreach ($datos['clientes'] as $cliente) {
            $fila++;
            $hoja->setCellValue("A{$fila}", $cliente['cliente']);
            $hoja->setCellValueExplicit("B{$fila}", $cliente['documento'], DataType::TYPE_STRING);
            $hoja->setCellValue("C{$fila}", $cliente['saldo']);
        }
        $fila++;
        $hoja->fromArray(['SALDO NETO DE CARTERA', '', $datos['neto']], null, 'A'.$fila);
        $this->cerrar($hoja, $fila, 'C', ['C']);
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function canastilla(Worksheet $hoja, array $datos, string $fechaCorte): void
    {
        $hoja->setTitle('Saldos de canastilla');
        $hoja->setCellValue('A1', 'INFORME DE SALDOS DE CANASTILLA');
        $hoja->setCellValue('A2', $fechaCorte);
        $this->encabezado($hoja, ['REFERENCIA', 'SALDO (UNIDADES)', 'COSTO PROMEDIO', 'VALOR AL COSTO']);

        $fila = 4;
        foreach ($datos['referencias'] as $referencia) {
            $fila++;
            $hoja->fromArray([$referencia['referencia'], $referencia['unidades'], $referencia['promedio'], $referencia['valor']], null, 'A'.$fila);
        }
        $fila++;
        $hoja->fromArray(['TOTALES', $datos['unidades'], null, $datos['valor']], null, 'A'.$fila);
        $hoja->getStyle("B5:B{$fila}")->getNumberFormat()->setFormatCode('#,##0.##');
        $this->cerrar($hoja, $fila, 'D', ['C', 'D']);
    }

    /**
     * @param  list<array<string, mixed>>  $datos
     */
    private function precios(Worksheet $hoja, array $datos): void
    {
        $hoja->setTitle('Precios de canastilla');
        $hoja->setCellValue('A1', 'INFORME DE PRECIOS DE VENTA DE CANASTILLA');
        $hoja->setCellValue('A2', 'PRECIOS VIGENTES AL '.now()->format('d/m/Y'));
        $this->encabezado($hoja, ['REFERENCIA', 'PRECIO ANTES DE IVA', 'IVA', 'TOTAL']);

        $fila = 4;
        foreach ($datos as $producto) {
            $fila++;
            $hoja->fromArray([$producto['referencia'], $producto['precio'], $producto['iva'], $producto['total']], null, 'A'.$fila);
        }
        $hoja->getStyle("B5:D{$fila}")->getNumberFormat()->setFormatCode(self::FORMATO_PESOS);
        $hoja->getStyle("A4:D{$fila}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }

    /**
     * @param  list<string>  $titulos
     */
    private function encabezado(Worksheet $hoja, array $titulos): void
    {
        $hoja->fromArray($titulos, null, 'A4');
        $rango = 'A4:'.chr(ord('A') + count($titulos) - 1).'4';
        $hoja->getStyle($rango)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $hoja->getStyle($rango)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F4E78');
    }

    /**
     * Fila de totales en negrita, formato de pesos y bordes.
     *
     * @param  list<string>  $columnasPesos
     */
    private function cerrar(Worksheet $hoja, int $fila, string $ultimaColumna, array $columnasPesos): void
    {
        $hoja->getStyle("A{$fila}:{$ultimaColumna}{$fila}")->getFont()->setBold(true);
        foreach ($columnasPesos as $columna) {
            $hoja->getStyle("{$columna}5:{$columna}{$fila}")->getNumberFormat()->setFormatCode(self::FORMATO_PESOS);
        }
        $hoja->getStyle("A4:{$ultimaColumna}{$fila}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }
}
