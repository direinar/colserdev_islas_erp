<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Kardex de inventario en Excel (gasolina, ACPM, canastilla por referencia y control),
 * con las mismas columnas de la pantalla.
 */
class KardexExcelService
{
    private const FORMATO_PESOS = '#,##0;[Red]-#,##0';

    private const FORMATO_CANTIDAD = '#,##0.00;[Red]-#,##0.00';

    /**
     * @param  array{rows: list<array<string, mixed>>, totales: array<string, float>}  $kardex
     */
    public function generar(string $titulo, string $unidad, array $kardex, string $desde, string $hasta, bool $mostrarReferencia = false): Spreadsheet
    {
        $libro = new Spreadsheet;
        $hoja = $libro->getActiveSheet();
        $hoja->setTitle('Kardex');

        $hoja->setCellValue('A1', $titulo);
        $hoja->setCellValue('A2', 'DESDE '.Carbon::parse($desde)->format('d/m/Y').'  HASTA '.Carbon::parse($hasta)->format('d/m/Y'));
        $hoja->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('FF0000');

        $encabezados = [
            'PLANILLA No.', 'FECHA', 'No. FC',
            ...($mostrarReferencia ? ['REFERENCIA'] : []),
            $unidad.' ENTRADAS', $unidad.' SALIDAS', $unidad.' SALDO',
            'VALOR ENTRADAS', 'VALOR SALIDAS', 'VALOR SALDO', 'PROMEDIO',
            'VALOR VENTAS', 'PRECIO ACTUAL',
        ];
        $ultima = Coordinate::stringFromColumnIndex(count($encabezados));
        $hoja->fromArray($encabezados, null, 'A4');
        $estilo = $hoja->getStyle("A4:{$ultima}4");
        $estilo->getFont()->setBold(true);
        $estilo->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setWrapText(true);
        $estilo->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F6E7D4');

        $fila = 4;
        foreach ($kardex['rows'] as $movimiento) {
            $fila++;
            $fecha = $movimiento['fecha_compra'] ?? $movimiento['fecha_venta'];
            $hoja->fromArray([
                $movimiento['numero_turno'],
                $fecha ? Carbon::parse($fecha)->format('d/m/Y') : null,
                $movimiento['factura'],
                ...($mostrarReferencia ? [$movimiento['referencia'] ?? ''] : []),
                $movimiento['entradas'] ?: null,
                $movimiento['salidas'] ?: null,
                $movimiento['saldo_unidades'],
                $movimiento['valor_entradas'] ?: null,
                $movimiento['salidas'] ? $movimiento['valor_salidas'] : null,
                $movimiento['valor_saldo'],
                $movimiento['promedio'],
                $movimiento['tipo'] === 'venta' ? $movimiento['valor_venta'] : null,
                $movimiento['precio'],
            ], null, 'A'.$fila, true);
        }

        $fila++;
        $totales = $kardex['totales'];
        $hoja->fromArray([
            'TOTALES', null, null,
            ...($mostrarReferencia ? [null] : []),
            $totales['entradas'], $totales['salidas'], $totales['saldo_unidades'],
            $totales['valor_entradas'], $totales['valor_salidas'], $totales['valor_saldo'], null,
            $totales['valor_venta'], null,
        ], null, 'A'.$fila, true);
        $hoja->getStyle("A{$fila}:{$ultima}{$fila}")->getFont()->setBold(true);

        $primeraCantidad = $mostrarReferencia ? 5 : 4;
        $cantidades = Coordinate::stringFromColumnIndex($primeraCantidad).'5:'.Coordinate::stringFromColumnIndex($primeraCantidad + 2).$fila;
        $valores = Coordinate::stringFromColumnIndex($primeraCantidad + 3).'5:'.$ultima.$fila;
        $hoja->getStyle($cantidades)->getNumberFormat()->setFormatCode(self::FORMATO_CANTIDAD);
        $hoja->getStyle($valores)->getNumberFormat()->setFormatCode(self::FORMATO_PESOS);
        $hoja->getStyle("A4:{$ultima}{$fila}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        foreach (range(1, count($encabezados)) as $columna) {
            $hoja->getColumnDimension(Coordinate::stringFromColumnIndex($columna))->setWidth(15);
        }
        if ($mostrarReferencia) {
            $hoja->getColumnDimension('D')->setWidth(30);
        }

        return $libro;
    }
}
