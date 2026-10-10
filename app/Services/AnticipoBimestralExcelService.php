<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Informe de anticipo bimestral en Excel con las secciones de la plantilla: compras de
 * combustible del bimestre, liquidación del bimestre, acumulado y liquidación del anticipo.
 */
class AnticipoBimestralExcelService
{
    private const FORMATO_PESOS = '#,##0';

    private const FORMATO_GALONES = '#,##0.##';

    private const FORMATO_MARGEN = '#,##0.00';

    private const ORDINALES = [1 => 'PRIMER', 2 => 'SEGUNDO', 3 => 'TERCER', 4 => 'CUARTO', 5 => 'QUINTO', 6 => 'SEXTO'];

    public function __construct(private AnticipoBimestralService $anticipo) {}

    public function generar(int $anio, int $bimestre): Spreadsheet
    {
        $informe = $this->anticipo->informe($anio, $bimestre);

        $libro = new Spreadsheet;
        $hoja = $libro->getActiveSheet();
        $hoja->setTitle('Anticipo bimestral');

        $hoja->setCellValue('A1', 'INFORME ANTICIPO BIMESTRAL');
        $hoja->setCellValue('A2', 'DESDE '.Carbon::parse($informe['desde'])->format('d/m/Y').'  HASTA '.Carbon::parse($informe['hasta'])->format('d/m/Y'));
        $hoja->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('FF0000');

        // Compras de combustible del bimestre (columnas A a H).
        $this->titulo($hoja, 'A4:H4', 'COMPRA DE COMBUSTIBLE EN BIMESTRE '.$bimestre);
        $hoja->fromArray(['FECHA', 'FACTURA', 'GASOLINA UNIDADES', 'GASOLINA DISTRIB. COSTO', 'ACPM UNIDADES', 'ACPM DISTRIB. COSTO', 'VR TOTAL FRA', 'TOTAL GALONES'], null, 'A5');
        $this->encabezado($hoja, 'A5:H5');

        $fila = 5;
        foreach ($informe['compras'] as $mes) {
            foreach ($mes['compras'] as $compra) {
                $fila++;
                $hoja->fromArray([
                    $compra->fecha->format('d/m/Y'),
                    (string) $compra->factura,
                    (float) $compra->gasolina,
                    (float) $compra->distribucion_gasolina,
                    (float) $compra->acpm,
                    (float) $compra->distribucion_acpm,
                    (float) $compra->vr_total_fra,
                    (float) $compra->gasolina + (float) $compra->acpm,
                ], null, 'A'.$fila);
            }
            $fila++;
            $hoja->fromArray(['TOTAL '.$mes['nombre'], null, $mes['gasolina'], null, $mes['acpm'], null, $mes['valor'], $mes['galones']], null, 'A'.$fila);
            $hoja->getStyle("A{$fila}:H{$fila}")->getFont()->setBold(true);
        }
        $this->bordes($hoja, "A5:H{$fila}");
        $hoja->getStyle("C6:C{$fila}")->getNumberFormat()->setFormatCode(self::FORMATO_GALONES);
        $hoja->getStyle("E6:E{$fila}")->getNumberFormat()->setFormatCode(self::FORMATO_GALONES);
        $hoja->getStyle("H6:H{$fila}")->getNumberFormat()->setFormatCode(self::FORMATO_GALONES);
        foreach (['D', 'F', 'G'] as $columna) {
            $hoja->getStyle("{$columna}6:{$columna}{$fila}")->getNumberFormat()->setFormatCode(self::FORMATO_PESOS);
        }

        // Liquidación del bimestre y acumulado (columnas J a M).
        $fila = $this->tablaMeses($hoja, 4, 'LIQUIDACIÓN '.self::ORDINALES[$bimestre].' BIMESTRE', 'MES', $informe['meses'], $informe['totalBimestre']);
        $fila = $this->tablaMeses($hoja, $fila + 2, 'ACUMULADO: ENERO A '.AnticipoBimestralService::MESES[$bimestre * 2].' '.$anio, 'PERIODO', $informe['acumulado']['filas'], $informe['acumulado']['total']);

        $liquidacion = $informe['liquidacion'];
        $inicio = $fila + 2;
        $this->titulo($hoja, "J{$inicio}:M{$inicio}", 'LIQUIDACIÓN DEL ANTICIPO');
        $filas = [
            ['VENTAS DE COMBUSTIBLE DE ESTE BIMESTRE', $liquidacion['ventas_combustible']],
            ['MÁS VENTAS DE LUBRICANTES', $liquidacion['ventas_lubricantes']],
            ['SUBTOTAL', $liquidacion['subtotal']],
            ['OTROS INGRESOS', $liquidacion['otros_ingresos']],
            ['BASE DE LIQUIDACIÓN RÉGIMEN SIMPLE', $liquidacion['base_simple']],
            ['MENOS DESCUENTOS EN VENTAS', $liquidacion['descuentos_ventas']],
            ['BASE DE LIQUIDACIÓN ICA', $liquidacion['base_ica']],
        ];
        $fila = $inicio;
        foreach ($filas as [$concepto, $valor]) {
            $fila++;
            $hoja->mergeCells("J{$fila}:L{$fila}")->setCellValue("J{$fila}", $concepto);
            $hoja->setCellValue("M{$fila}", $valor);
        }
        foreach ([$inicio + 3, $inicio + 5, $inicio + 7] as $destacada) {
            $hoja->getStyle("J{$destacada}:M{$destacada}")->getFont()->setBold(true);
        }
        $hoja->getStyle('M'.($inicio + 1).":M{$fila}")->getNumberFormat()->setFormatCode(self::FORMATO_PESOS);
        $this->bordes($hoja, "J{$inicio}:M{$fila}");

        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K', 'L', 'M'] as $columna) {
            $hoja->getColumnDimension($columna)->setAutoSize(true);
        }

        return $libro;
    }

    /**
     * @param  list<array{nombre: string, galones: float, margen: float|null, pesos: float}>  $filas
     * @param  array{galones: float, pesos: float}  $total
     */
    private function tablaMeses(Worksheet $hoja, int $inicio, string $titulo, string $primeraColumna, array $filas, array $total): int
    {
        $this->titulo($hoja, "J{$inicio}:M{$inicio}", $titulo);
        $hoja->fromArray([$primeraColumna, 'GALONES', 'VR. INTERMEDIACIÓN', 'PESOS'], null, 'J'.($inicio + 1));
        $this->encabezado($hoja, 'J'.($inicio + 1).':M'.($inicio + 1));

        $fila = $inicio + 1;
        foreach ($filas as $dato) {
            $fila++;
            $hoja->fromArray([$dato['nombre'], $dato['galones'], $dato['margen'] ?? 'Varios', $dato['pesos']], null, 'J'.$fila);
        }
        $fila++;
        $hoja->fromArray(['TOTAL', $total['galones'], null, $total['pesos']], null, 'J'.$fila);
        $hoja->getStyle("J{$fila}:M{$fila}")->getFont()->setBold(true);

        $hoja->getStyle('K'.($inicio + 2).":K{$fila}")->getNumberFormat()->setFormatCode(self::FORMATO_GALONES);
        $hoja->getStyle('L'.($inicio + 2).":L{$fila}")->getNumberFormat()->setFormatCode(self::FORMATO_MARGEN);
        $hoja->getStyle('M'.($inicio + 2).":M{$fila}")->getNumberFormat()->setFormatCode(self::FORMATO_PESOS);
        $this->bordes($hoja, "J{$inicio}:M{$fila}");

        return $fila;
    }

    private function titulo(Worksheet $hoja, string $rango, string $texto): void
    {
        $hoja->mergeCells($rango);
        $celda = explode(':', $rango)[0];
        $hoja->setCellValue($celda, $texto);
        $estilo = $hoja->getStyle($rango);
        $estilo->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $estilo->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F4E78');
    }

    private function encabezado(Worksheet $hoja, string $rango): void
    {
        $estilo = $hoja->getStyle($rango);
        $estilo->getFont()->setBold(true);
        $estilo->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DDEBF7');
    }

    private function bordes(Worksheet $hoja, string $rango): void
    {
        $hoja->getStyle($rango)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }
}
