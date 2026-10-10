<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Comprobante contable de compras en Excel con las columnas de la plantilla:
 * CUENTA, CONCEPTO, TERCERO, NIT, DÉBITO y CRÉDITO, con totales al final.
 */
class ComprobanteContableComprasExcelService
{
    private const FORMATO_DINERO = '#,##0;[Red]-#,##0';

    public function __construct(private ComprobanteContableComprasService $comprobante) {}

    public function generar(string $desde, string $hasta): Spreadsheet
    {
        $datos = $this->comprobante->generar($desde, $hasta);

        $libro = new Spreadsheet;
        $hoja = $libro->getActiveSheet();
        $hoja->setTitle('Comprobante compras');

        $hoja->setCellValue('A1', 'COMPROBANTE CONTABLE DE COMPRAS');
        $hoja->setCellValue('A2', 'DESDE '.Carbon::parse($desde)->format('d/m/Y').'  HASTA '.Carbon::parse($hasta)->format('d/m/Y'));
        $hoja->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('FF0000');

        $hoja->fromArray(['CUENTA', 'CONCEPTO', 'TERCERO', 'NIT', 'DEBITO', 'CREDITO'], null, 'A4');
        $encabezado = $hoja->getStyle('A4:F4');
        $encabezado->getFont()->setBold(true);
        $encabezado->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $encabezado->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('CCCCFF');

        $fila = 4;
        foreach ($datos['lineas'] as $linea) {
            $fila++;
            // Cuenta y NIT como texto para que Excel no les quite ceros ni los vuelva notación científica.
            $hoja->setCellValueExplicit("A{$fila}", $linea['cuenta'], DataType::TYPE_STRING);
            $hoja->setCellValue("B{$fila}", $linea['concepto']);
            $hoja->setCellValue("C{$fila}", $linea['tercero']);
            $hoja->setCellValueExplicit("D{$fila}", $linea['nit'], DataType::TYPE_STRING);
            $hoja->setCellValue("E{$fila}", $linea['debito'] ?: null);
            $hoja->setCellValue("F{$fila}", $linea['credito'] ?: null);
        }

        $fila++;
        $hoja->fromArray(['', 'TOTALES', '', '', $datos['totales']['debito'], $datos['totales']['credito']], null, 'A'.$fila);
        $hoja->getStyle("A{$fila}:F{$fila}")->getFont()->setBold(true);

        $hoja->getStyle("E5:F{$fila}")->getNumberFormat()->setFormatCode(self::FORMATO_DINERO);
        $hoja->getStyle("A4:F{$fila}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        foreach (range('A', 'F') as $columna) {
            $hoja->getColumnDimension($columna)->setAutoSize(true);
        }

        return $libro;
    }
}
