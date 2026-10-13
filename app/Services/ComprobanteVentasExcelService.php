<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Comprobante contable de ventas del periodo en Excel.
 */
class ComprobanteVentasExcelService
{
    private const FORMATO_PESOS = '#,##0;[Red]-#,##0';

    public function __construct(private ControlCajaService $controlCaja) {}

    public function generar(string $desde, string $hasta): Spreadsheet
    {
        $comprobante = $this->controlCaja->comprobanteVentas($desde, $hasta)['comprobante'];

        $libro = new Spreadsheet;
        $hoja = $libro->getActiveSheet();
        $hoja->setTitle('Comprobante de ventas');

        $hoja->setCellValue('A1', 'COMPROBANTE CONTABLE DE VENTAS');
        $hoja->setCellValue('A2', 'DESDE '.Carbon::parse($desde)->format('d/m/Y').'  HASTA '.Carbon::parse($hasta)->format('d/m/Y'));
        $hoja->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('FF0000');

        $hoja->fromArray(['CUENTA', 'CONCEPTO', 'CLIENTE/TERCERO', 'NIT', 'DEBITO', 'CREDITO'], null, 'A4');
        $hoja->getStyle('A4:F4')->getFont()->setBold(true);
        $hoja->getStyle('A4:F4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('CCCCFF');

        $fila = 4;
        foreach ($comprobante['lineas'] as $linea) {
            $fila++;
            $hoja->setCellValueExplicit("A{$fila}", $linea['cuenta'], DataType::TYPE_STRING);
            $hoja->setCellValue("B{$fila}", $linea['concepto']);
            $hoja->setCellValue("C{$fila}", $linea['tercero']);
            // El NIT como texto, para que Excel no le quite ceros ni lo vuelva número.
            $hoja->setCellValueExplicit("D{$fila}", $linea['nit'], DataType::TYPE_STRING);
            $hoja->setCellValue("E{$fila}", $linea['debito'] ?: null);
            $hoja->setCellValue("F{$fila}", $linea['credito'] ?: null);
        }
        $fila++;
        $hoja->fromArray(['', 'TOTALES', '', '', $comprobante['debito'], $comprobante['credito']], null, 'A'.$fila);
        $hoja->fromArray(['', 'DIFERENCIA', '', '', $comprobante['diferencia']], null, 'A'.($fila + 1));
        $hoja->getStyle("A{$fila}:F".($fila + 1))->getFont()->setBold(true);
        $hoja->getStyle('E5:F'.($fila + 1))->getNumberFormat()->setFormatCode(self::FORMATO_PESOS);
        $hoja->getStyle("A4:F{$fila}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        foreach (['A' => 14, 'B' => 36, 'C' => 30, 'D' => 14, 'E' => 16, 'F' => 16] as $columna => $ancho) {
            $hoja->getColumnDimension($columna)->setWidth($ancho);
        }

        return $libro;
    }
}
