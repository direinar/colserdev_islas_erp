<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Informe de recaudos de cartera en Excel: los recaudos del periodo (columnas A a F)
 * y el comprobante contable al lado (columnas H a K), como en la plantilla.
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
        $hoja->setCellValue('H1', 'COMPROBANTE CONTABLE - RECAUDOS DE CARTERA');
        $hoja->setCellValue('A2', 'DESDE '.Carbon::parse($desde)->format('d/m/Y').'  HASTA '.Carbon::parse($hasta)->format('d/m/Y'));
        $hoja->getStyle('A1:H1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB('FF0000');

        $this->encabezado($hoja, 'A4', ['PLANILLA', 'FECHA', 'CLIENTE', 'BANCO', 'CONCEPTO', 'VALOR']);
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

        $this->encabezado($hoja, 'H4', ['CUENTA', 'NOMBRE', 'DEBITO', 'CREDITO']);
        $fila = 4;
        foreach ($informe['comprobante']['lineas'] as $linea) {
            $fila++;
            $hoja->setCellValueExplicit("H{$fila}", $linea['cuenta'], DataType::TYPE_STRING);
            $hoja->setCellValue("I{$fila}", $linea['nombre']);
            $hoja->setCellValue("J{$fila}", $linea['debito'] ?: null);
            $hoja->setCellValue("K{$fila}", $linea['credito'] ?: null);
        }
        $fila++;
        $hoja->fromArray(['TOTALES', $informe['comprobante']['debito'], $informe['comprobante']['credito']], null, 'I'.$fila);
        $hoja->fromArray(['DIFERENCIA', $informe['comprobante']['diferencia']], null, 'I'.($fila + 1));
        $hoja->getStyle("H{$fila}:K".($fila + 1))->getFont()->setBold(true);
        $hoja->getStyle('J5:K'.($fila + 1))->getNumberFormat()->setFormatCode(self::FORMATO_PESOS);
        $hoja->getStyle("H4:K{$fila}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'H', 'I', 'J', 'K'] as $columna) {
            $hoja->getColumnDimension($columna)->setAutoSize(true);
        }

        return $libro;
    }

    /**
     * @param  list<string>  $titulos
     */
    private function encabezado(Worksheet $hoja, string $celda, array $titulos): void
    {
        $hoja->fromArray($titulos, null, $celda);
        $fila = (int) preg_replace('/\D/', '', $celda);
        $inicio = preg_replace('/\d/', '', $celda);
        $fin = chr(ord($inicio) + count($titulos) - 1);
        $estilo = $hoja->getStyle("{$inicio}{$fila}:{$fin}{$fila}");
        $estilo->getFont()->setBold(true);
        $estilo->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DDEBF7');
    }
}
