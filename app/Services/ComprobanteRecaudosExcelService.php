<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Comprobante contable de recaudos de cartera del periodo en Excel.
 */
class ComprobanteRecaudosExcelService
{
    private const FORMATO_PESOS = '#,##0';

    public function __construct(private RecaudosCarteraService $recaudos) {}

    public function generar(string $desde, string $hasta): Spreadsheet
    {
        $comprobante = $this->recaudos->comprobanteRecaudos($desde, $hasta)['comprobante'];

        $libro = new Spreadsheet;
        $hoja = $libro->getActiveSheet();
        $hoja->setTitle('Comprobante de recaudos');

        $hoja->setCellValue('A1', 'COMPROBANTE CONTABLE DE RECAUDOS DE CARTERA');
        $hoja->setCellValue('A2', 'DESDE '.Carbon::parse($desde)->format('d/m/Y').'  HASTA '.Carbon::parse($hasta)->format('d/m/Y'));
        $hoja->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB('FF0000');

        $hoja->fromArray(['CUENTA', 'NOMBRE', 'DEBITO', 'CREDITO'], null, 'A4');
        $hoja->getStyle('A4:D4')->getFont()->setBold(true);
        $hoja->getStyle('A4:D4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DDEBF7');

        $fila = 4;
        foreach ($comprobante['lineas'] as $linea) {
            $fila++;
            $hoja->setCellValueExplicit("A{$fila}", $linea['cuenta'], DataType::TYPE_STRING);
            $hoja->setCellValue("B{$fila}", $linea['nombre']);
            $hoja->setCellValue("C{$fila}", $linea['debito'] ?: null);
            $hoja->setCellValue("D{$fila}", $linea['credito'] ?: null);
        }
        $fila++;
        $hoja->fromArray(['TOTALES', $comprobante['debito'], $comprobante['credito']], null, 'B'.$fila);
        $hoja->fromArray(['DIFERENCIA', $comprobante['diferencia']], null, 'B'.($fila + 1));
        $hoja->getStyle("A{$fila}:D".($fila + 1))->getFont()->setBold(true);
        $hoja->getStyle('C5:D'.($fila + 1))->getNumberFormat()->setFormatCode(self::FORMATO_PESOS);
        $hoja->getStyle("A4:D{$fila}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        foreach (['A', 'B', 'C', 'D'] as $columna) {
            $hoja->getColumnDimension($columna)->setAutoSize(true);
        }

        return $libro;
    }
}
