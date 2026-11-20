<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Comprobante contable de conteo en Excel, con las columnas de la plantilla:
 * CUENTA, CONCEPTO, TERCERO, NIT, DÉBITO y CRÉDITO.
 */
class ComprobanteConteoExcelService
{
    private const FORMATO_PESOS = '#,##0;[Red]-#,##0';

    public function __construct(private ComprobanteConteoService $comprobantes) {}

    public function generar(string $desde, string $hasta): Spreadsheet
    {
        $periodo = $this->comprobantes->delPeriodo($desde, $hasta);

        $libro = new Spreadsheet;
        $hoja = $libro->getActiveSheet();
        $hoja->setTitle('Comprobante conteo');
        $hoja->setCellValue('A1', 'COMPROBANTE CONTABLE DE CONTEO');
        $hoja->setCellValue('A2', 'DESDE '.Carbon::parse($desde)->format('d/m/Y').' HASTA '.Carbon::parse($hasta)->format('d/m/Y'));
        $hoja->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $hoja->fromArray(['CUENTA', 'CONCEPTO', 'TERCERO', 'NIT', 'DEBITO', 'CREDITO'], null, 'A4');
        $hoja->getStyle('A4:F4')->getFont()->setBold(true);
        $hoja->getStyle('A4:F4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EEECE1');

        $fila = 4;
        foreach ($periodo['comprobantes'] as $comprobante) {
            foreach ($comprobante['lineas'] as $linea) {
                $fila++;
                // La cuenta y el NIT como texto, para no perder ceros ni formato.
                $hoja->setCellValueExplicit('A'.$fila, (string) $linea['cuenta'], DataType::TYPE_STRING);
                $hoja->setCellValue('B'.$fila, $linea['concepto']);
                $hoja->setCellValue('C'.$fila, $linea['tercero']);
                $hoja->setCellValueExplicit('D'.$fila, (string) $linea['nit'], DataType::TYPE_STRING);
                $hoja->setCellValue('E'.$fila, $linea['debito'] ?: null);
                $hoja->setCellValue('F'.$fila, $linea['credito'] ?: null);
            }
        }

        $fila++;
        $hoja->setCellValue('B'.$fila, 'SUMAS IGUALES');
        $hoja->setCellValue('E'.$fila, $periodo['debito']);
        $hoja->setCellValue('F'.$fila, $periodo['credito']);
        $hoja->getStyle("A{$fila}:F{$fila}")->getFont()->setBold(true);
        $hoja->getStyle("E5:F{$fila}")->getNumberFormat()->setFormatCode(self::FORMATO_PESOS);
        $hoja->getStyle("A4:F{$fila}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        foreach (['A' => 14, 'B' => 52, 'C' => 32, 'D' => 14, 'E' => 16, 'F' => 16] as $columna => $ancho) {
            $hoja->getColumnDimension($columna)->setWidth($ancho);
        }

        return $libro;
    }
}
