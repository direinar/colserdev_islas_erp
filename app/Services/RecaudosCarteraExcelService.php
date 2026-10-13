<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Informe de recaudos de cartera en Excel: los recaudos del periodo. El comprobante
 * contable se exporta en ComprobanteRecaudosExcelService.
 */
class RecaudosCarteraExcelService
{
    private const FORMATO_PESOS = '#,##0';

    public function __construct(private RecaudosCarteraService $recaudos) {}

    public function generar(string $desde, string $hasta): Spreadsheet
    {
        $informe = $this->recaudos->informe($desde, $hasta);

        $libro = new Spreadsheet;
        $hoja = $libro->getActiveSheet();
        $hoja->setTitle('Recaudos de cartera');

        $hoja->setCellValue('A1', 'INFORME DE RECAUDOS DE CARTERA');
        $hoja->setCellValue('A2', 'DESDE '.Carbon::parse($desde)->format('d/m/Y').'  HASTA '.Carbon::parse($hasta)->format('d/m/Y'));
        $hoja->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB('FF0000');

        $hoja->fromArray(['PLANILLA', 'FECHA', 'CLIENTE', 'BANCO', 'CONCEPTO', 'VALOR'], null, 'A4');
        $hoja->getStyle('A4:F4')->getFont()->setBold(true);
        $hoja->getStyle('A4:F4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DDEBF7');

        $fila = 4;
        foreach ($informe['recaudos'] as $recaudo) {
            $fila++;
            $hoja->fromArray([
                $recaudo['planilla'],
                Carbon::parse($recaudo['fecha'])->format('d/m/Y'),
                $recaudo['cliente'],
                $recaudo['banco'],
                $recaudo['concepto'],
                $recaudo['valor'],
            ], null, 'A'.$fila);
        }
        $fila++;
        $hoja->setCellValue("E{$fila}", 'TOTAL DEL PERIODO');
        $hoja->setCellValue("F{$fila}", $informe['total']);
        $hoja->getStyle("A{$fila}:F{$fila}")->getFont()->setBold(true);
        $hoja->getStyle("F5:F{$fila}")->getNumberFormat()->setFormatCode(self::FORMATO_PESOS);
        $hoja->getStyle("A4:F{$fila}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $columna) {
            $hoja->getColumnDimension($columna)->setAutoSize(true);
        }

        return $libro;
    }
}
