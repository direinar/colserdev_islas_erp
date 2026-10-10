<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Informe de margen mayorista en Excel: facturas del periodo con su valor y galones,
 * y la liquidación galones x porcentaje x margen.
 */
class MargenMayoristaExcelService
{
    public function __construct(private MargenMayoristaService $margenMayorista) {}

    public function generar(string $desde, string $hasta): Spreadsheet
    {
        $informe = $this->margenMayorista->informe($desde, $hasta);

        $libro = new Spreadsheet;
        $hoja = $libro->getActiveSheet();
        $hoja->setTitle('Margen mayorista');

        $hoja->setCellValue('A1', 'INFORME MARGEN MAYORISTA');
        $hoja->setCellValue('A2', 'DESDE '.Carbon::parse($desde)->format('d/m/Y').'  HASTA '.Carbon::parse($hasta)->format('d/m/Y'));
        $hoja->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('FF0000');

        $hoja->fromArray(['FECHA', 'No. FACTURA', 'VR. FACTURAS', 'GALONES GASOLINA', 'GALONES ACPM', 'GALONES'], null, 'A4');
        $hoja->getStyle('A4:F4')->getFont()->setBold(true);
        $hoja->getStyle('A4:F4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DDEBF7');

        $fila = 4;
        foreach ($informe['facturas'] as $factura) {
            $fila++;
            $hoja->fromArray([
                Carbon::parse($factura['fecha'])->format('d/m/Y'),
                $factura['factura'],
                $factura['valor'],
                $factura['gasolina'],
                $factura['acpm'],
                $factura['galones'],
            ], null, 'A'.$fila);
        }
        $fila++;
        $totales = $informe['totales'];
        $hoja->fromArray(['', 'TOTALES', $totales['valor'], $totales['gasolina'], $totales['acpm'], $totales['galones']], null, 'A'.$fila);
        $hoja->getStyle("A{$fila}:F{$fila}")->getFont()->setBold(true);
        $hoja->getStyle("C5:C{$fila}")->getNumberFormat()->setFormatCode('#,##0');
        $hoja->getStyle("D5:F{$fila}")->getNumberFormat()->setFormatCode('#,##0.##');
        $hoja->getStyle("A4:F{$fila}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $inicio = $fila + 2;
        $hoja->fromArray(['GALONES', '% MAYORISTA', 'MARGEN', 'VALOR'], null, 'C'.$inicio);
        $hoja->getStyle("C{$inicio}:F{$inicio}")->getFont()->setBold(true);
        $hoja->getStyle("C{$inicio}:F{$inicio}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DDEBF7');

        $fila = $inicio;
        foreach ($informe['liquidacion'] as $grupo) {
            $fila++;
            $hoja->fromArray([$grupo['galones'], $grupo['porcentaje'], $grupo['margen'], $grupo['valor']], null, 'C'.$fila);
        }
        $fila++;
        $hoja->setCellValue("C{$fila}", 'MARGEN MAYORISTA');
        $hoja->setCellValue("F{$fila}", $informe['margen_mayorista']);
        $hoja->getStyle("C{$fila}:F{$fila}")->getFont()->setBold(true);
        $hoja->getStyle('C'.($inicio + 1).":C{$fila}")->getNumberFormat()->setFormatCode('#,##0.##');
        $hoja->getStyle('E'.($inicio + 1).":E{$fila}")->getNumberFormat()->setFormatCode('#,##0.00');
        $hoja->getStyle('F'.($inicio + 1).":F{$fila}")->getNumberFormat()->setFormatCode('#,##0');
        $hoja->getStyle("C{$inicio}:F{$fila}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        foreach (range('A', 'F') as $columna) {
            $hoja->getColumnDimension($columna)->setAutoSize(true);
        }

        return $libro;
    }
}
