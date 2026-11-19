<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Informe para Gobernación y SICOM en Excel, con los tres bloques de la plantilla.
 */
class InformeGobernacionSicomExcelService
{
    private const FORMATO_LECTURA = '#,##0.000';

    private const FORMATO_GALONES = '#,##0.00;[Red]-#,##0.00';

    public function __construct(private InformeGobernacionSicomService $informe) {}

    public function generar(string $mes): Spreadsheet
    {
        $datos = $this->informe->informe($mes);
        $libro = new Spreadsheet;
        $hoja = $libro->getActiveSheet();
        $hoja->setTitle('Gobernación y SICOM');

        $hoja->setCellValue('A1', 'INFORME PARA GOBERNACIÓN Y SICOM');
        $hoja->setCellValue('A2', 'MES: '.mb_strtoupper(Carbon::parse($datos['desde'])->translatedFormat('F Y')));
        $hoja->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('1F4E78');
        $hoja->getStyle('A2')->getFont()->setBold(true);

        $fila = $this->lecturas($hoja, $datos['lecturas'], 4);
        $fila = $this->compras($hoja, $datos['compras'], $fila + 2);
        $this->sicom($hoja, $datos['sicom'], $fila + 2);

        foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $columna) {
            $hoja->getColumnDimension($columna)->setAutoSize(true);
        }

        return $libro;
    }

    /**
     * @param  array<string, mixed>  $lecturas
     */
    private function lecturas(Worksheet $hoja, array $lecturas, int $fila): int
    {
        $this->titulo($hoja, $fila, '1 - LECTURAS ELECTRÓNICAS');
        $encabezado = $fila + 1;
        $this->encabezado($hoja, $encabezado, ['MANGUERAS', 'INICIAL DEL MES (1)', 'FINAL DEL MES (2)', 'VENTAS PLUS (3)', 'VENTAS ACPM (4)', 'TOTAL']);

        $fila = $encabezado;
        foreach ($lecturas['filas'] as $lectura) {
            $fila++;
            $hoja->fromArray([$lectura['manguera'], $lectura['inicial'], $lectura['final'], $lectura['plus'] ?: null, $lectura['acpm'] ?: null, $lectura['total']], null, 'A'.$fila);
        }
        $fila++;
        $hoja->fromArray(['TOTALES', null, null, $lecturas['totales']['plus'], $lecturas['totales']['acpm'], $lecturas['totales']['total']], null, 'A'.$fila);

        $hoja->getStyle('B'.($encabezado + 1).":F{$fila}")->getNumberFormat()->setFormatCode(self::FORMATO_LECTURA);
        $this->cerrar($hoja, $encabezado, $fila, 'F');

        $notas = [
            '(1) Lectura del último día del mes anterior (la final del informe anterior).',
            '(2) Lectura del último día del mes del informe (será la inicial del mes siguiente).',
            '(3) y (4) Diferencia (2) − (1) de PLUS y de ACPM.',
        ];
        foreach ($notas as $nota) {
            $fila++;
            $hoja->setCellValue("A{$fila}", $nota);
            $hoja->getStyle("A{$fila}")->getFont()->setItalic(true)->setSize(9);
        }

        return $fila;
    }

    /**
     * @param  array<string, mixed>  $compras
     */
    private function compras(Worksheet $hoja, array $compras, int $fila): int
    {
        $this->titulo($hoja, $fila, '2 - COMPRAS DEL MES');
        $encabezado = $fila + 1;
        $this->encabezado($hoja, $encabezado, ['FECHA', 'No. FACTURA', 'GALONES PLUS', 'GALONES ACPM', 'TOTAL']);

        $fila = $encabezado;
        foreach ($compras['filas'] as $compra) {
            $fila++;
            $hoja->fromArray([Carbon::parse($compra['fecha'])->format('d/m/Y'), $compra['factura'], $compra['plus'], $compra['acpm'], $compra['total']], null, 'A'.$fila);
        }
        $fila++;
        $hoja->fromArray(['TOTALES', null, $compras['totales']['plus'], $compras['totales']['acpm'], $compras['totales']['total']], null, 'A'.$fila);

        $hoja->getStyle('C'.($encabezado + 1).":E{$fila}")->getNumberFormat()->setFormatCode(self::FORMATO_GALONES);
        $this->cerrar($hoja, $encabezado, $fila, 'E');

        return $fila;
    }

    /**
     * @param  array<string, array<string, float>>  $sicom
     */
    private function sicom(Worksheet $hoja, array $sicom, int $fila): void
    {
        $this->titulo($hoja, $fila, '3 - RESUMEN DEL MES PARA DECLARACIÓN DE SICOM');
        $encabezado = $fila + 1;
        $this->encabezado($hoja, $encabezado, ['CONCEPTO', 'CORRIENTE', 'ACPM', 'TOTAL']);

        $conceptos = [
            'saldo_inicio' => 'SALDO AL INICIO DEL MES',
            'compras' => 'Más COMPRAS del mes',
            'ventas' => 'Menos VENTAS del mes',
            'ajustes' => 'Ajustes del mes',
            'saldo_cierre' => 'SALDO AL CIERRE DEL MES',
            'saldo_inventario' => 'SALDO GALONES AL CIERRE DEL MES (INVENTARIO)',
            'diferencia' => 'DIFERENCIA',
        ];

        $fila = $encabezado;
        foreach ($conceptos as $clave => $concepto) {
            $fila++;
            $hoja->fromArray([$concepto, $sicom['corriente'][$clave], $sicom['acpm'][$clave], $sicom['total'][$clave]], null, 'A'.$fila);

            if (in_array($clave, ['saldo_cierre', 'diferencia'], true)) {
                $hoja->getStyle("A{$fila}:D{$fila}")->getFont()->setBold(true);
            }
        }

        $hoja->getStyle('B'.($encabezado + 1).":D{$fila}")->getNumberFormat()->setFormatCode(self::FORMATO_GALONES);
        $hoja->getStyle("A{$encabezado}:D{$fila}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }

    private function titulo(Worksheet $hoja, int $fila, string $titulo): void
    {
        $hoja->setCellValue("A{$fila}", $titulo);
        $hoja->getStyle("A{$fila}")->getFont()->setBold(true)->getColor()->setRGB('1F4E78');
    }

    /**
     * @param  list<string>  $titulos
     */
    private function encabezado(Worksheet $hoja, int $fila, array $titulos): void
    {
        $hoja->fromArray($titulos, null, 'A'.$fila);
        $rango = "A{$fila}:".chr(ord('A') + count($titulos) - 1).$fila;
        $hoja->getStyle($rango)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $hoja->getStyle($rango)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F4E78');
    }

    /**
     * Fila de totales en negrita y bordes de la tabla.
     */
    private function cerrar(Worksheet $hoja, int $encabezado, int $fila, string $ultimaColumna): void
    {
        $hoja->getStyle("A{$fila}:{$ultimaColumna}{$fila}")->getFont()->setBold(true);
        $hoja->getStyle("A{$encabezado}:{$ultimaColumna}{$fila}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }
}
