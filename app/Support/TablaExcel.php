<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Libro de Excel de un listado simple (catálogos y consultas): título, subtítulo opcional,
 * encabezados y filas. Las columnas de texto indicadas se guardan como texto (NIT, facturas).
 */
class TablaExcel
{
    private const FORMATO_NUMERO = '#,##0;[Red]-#,##0';

    /**
     * @param  list<string>  $encabezados
     * @param  iterable<int, list<mixed>>  $filas
     * @param  list<int>  $columnasTexto  Índices (desde 0) que se escriben como texto.
     * @param  list<mixed>|null  $totales  Fila de totales al final, opcional.
     */
    public static function generar(string $titulo, ?string $subtitulo, array $encabezados, iterable $filas, array $columnasTexto = [], ?array $totales = null): Spreadsheet
    {
        $libro = new Spreadsheet;
        $hoja = $libro->getActiveSheet();
        // Excel no admite estos caracteres ni más de 31 en el nombre de la hoja.
        $hoja->setTitle(mb_substr(str_replace(['\\', '/', '?', '*', '[', ']', ':'], ' ', $titulo), 0, 31));

        $hoja->setCellValue('A1', mb_strtoupper($titulo));
        $hoja->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('FF0000');
        if ($subtitulo) {
            $hoja->setCellValue('A2', $subtitulo);
        }

        $ultima = Coordinate::stringFromColumnIndex(count($encabezados));
        $hoja->fromArray($encabezados, null, 'A4');
        $hoja->getStyle("A4:{$ultima}4")->getFont()->setBold(true);
        $hoja->getStyle("A4:{$ultima}4")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DDEBF7');

        $fila = 4;
        foreach ($filas as $valores) {
            $fila++;
            foreach (array_values($valores) as $indice => $valor) {
                $celda = Coordinate::stringFromColumnIndex($indice + 1).$fila;
                in_array($indice, $columnasTexto, true)
                    ? $hoja->setCellValueExplicit($celda, (string) $valor, DataType::TYPE_STRING)
                    : $hoja->setCellValue($celda, $valor);
            }
        }

        if ($totales !== null) {
            $fila++;
            $hoja->fromArray($totales, null, 'A'.$fila, true);
            $hoja->getStyle("A{$fila}:{$ultima}{$fila}")->getFont()->setBold(true);
        }

        if ($fila > 4) {
            $hoja->getStyle("A5:{$ultima}{$fila}")->getNumberFormat()->setFormatCode(self::FORMATO_NUMERO);
        }
        $hoja->getStyle("A4:{$ultima}{$fila}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        foreach (range(1, count($encabezados)) as $columna) {
            $hoja->getColumnDimension(Coordinate::stringFromColumnIndex($columna))->setAutoSize(true);
        }

        return $libro;
    }
}
