<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Informe de cierre en Excel: estado de resultados del periodo con la referencia y el
 * origen de cada renglón, y las estadísticas del periodo, como en la plantilla.
 */
class InformeCierreExcelService
{
    private const FORMATO_PESOS = '#,##0;[Red]-#,##0';

    public function __construct(private InformeCierreService $cierre) {}

    public function generar(string $desde, string $hasta): Spreadsheet
    {
        $informe = $this->cierre->informe($desde, $hasta);
        $valores = $informe['valores'];
        $estadisticas = $informe['estadisticas'];

        $libro = new Spreadsheet;
        $hoja = $libro->getActiveSheet();
        $hoja->setTitle('Informe de cierre');

        $hoja->setCellValue('A1', 'INFORME DE CIERRE');
        $hoja->setCellValue('A2', 'DESDE '.Carbon::parse($desde)->format('d/m/Y').'  HASTA '.Carbon::parse($hasta)->format('d/m/Y'));
        $hoja->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('1F4E78');

        $hoja->fromArray(['CONCEPTO', 'VALOR', 'REF.', 'DE DÓNDE SE TOMA LA INFORMACIÓN'], null, 'A4');
        $hoja->getStyle('A4:D4')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $hoja->getStyle('A4:D4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F4E78');

        $renglones = [
            ['VENTAS DE ACPM', $valores['ventas_acpm'], '1', 'Inventario ACPM: VR. VENTAS del periodo', false],
            ['VENTAS DE GASOLINA', $valores['ventas_gasolina'], '2', 'Inventario gasolina: VR. VENTAS del periodo', false],
            ['TOTAL VENTAS DEL PERIODO', $valores['total_ventas'], '3', '1 + 2', true],
            ['COSTO DEL COMBUSTIBLE VENDIDO', $valores['costo_combustible'], '4', 'Inventarios ACPM + gasolina: salidas al costo promedio', false],
            ['SUBTOTAL', $valores['subtotal'], '5', '3 - 4', true],
            ['MENOS: DESCUENTOS', $valores['descuentos'], '6', 'Descuentos en ventas: puntos redimidos + descuentos', false],
            ['UTILIDAD BRUTA EN COMBUSTIBLES', $valores['utilidad_combustibles'], '7', '5 - 6', true],
            ['MARGEN MAYORISTA', $valores['margen_mayorista'], '8', 'Informe Margen Mayorista del mismo periodo', false],
            ['INGRESOS TOTALES POR COMBUSTIBLES', $valores['ingresos_combustibles'], '9', '7 + 8', true],
            ['VENTA DE CANASTILLA', $valores['venta_canastilla'], '10', 'Inventario Canastilla - Control: VR. VENTA (sin IVA)', false],
            ['COSTO DE VENTAS DE CANASTILLA', $valores['costo_canastilla'], '', 'Inventario Canastilla - Control: salidas al costo promedio', false],
            ['UTILIDAD EN CANASTILLA', $valores['utilidad_canastilla'], '11', '10 - costo', true],
            ['UTILIDAD BRUTA TOTAL EDS', $valores['utilidad_total'], '9+11', '', true],
        ];

        $fila = 4;
        foreach ($renglones as [$concepto, $valor, $referencia, $origen, $destacado]) {
            $fila++;
            $hoja->fromArray([$concepto, $valor, $referencia, $origen], null, 'A'.$fila);
            if ($destacado) {
                $hoja->getStyle("A{$fila}:B{$fila}")->getFont()->setBold(true);
                $hoja->getStyle("A{$fila}:D{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF2CC');
            }
        }
        $hoja->getStyle("B5:B{$fila}")->getNumberFormat()->setFormatCode(self::FORMATO_PESOS);
        $hoja->getStyle("A4:D{$fila}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $inicio = $fila + 2;
        $hoja->setCellValue("A{$inicio}", 'ESTADÍSTICAS DEL PERIODO');
        $hoja->getStyle("A{$inicio}")->getFont()->setBold(true);
        $estadisticasFilas = [
            ['MARGEN DE INTERMEDIACIÓN', $estadisticas['margen_intermediacion'], '12', '7 / 3', '0.00%'],
            ['GALONAJE DEL PERIODO', $estadisticas['galonaje_periodo'], '13', 'Galones vendidos (gasolina + ACPM)', '#,##0'],
            ['GALONAJE ACUMULADO', $estadisticas['galonaje_acumulado'], '14', 'Saldo inicial + galones vendidos desde la fecha de corte', '#,##0'],
            ['SALDO DEL CONTRATO', $estadisticas['saldo_contrato'], '15', 'Saldo inicial - galones vendidos desde la fecha de corte', '#,##0'],
        ];
        $fila = $inicio;
        foreach ($estadisticasFilas as [$concepto, $valor, $referencia, $origen, $formato]) {
            $fila++;
            $hoja->fromArray([$concepto, $valor, $referencia, $origen], null, 'A'.$fila);
            $hoja->getStyle("B{$fila}")->getNumberFormat()->setFormatCode($formato);
        }
        $hoja->getStyle('A'.($inicio + 1).":D{$fila}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $hoja->getColumnDimension('A')->setWidth(38);
        $hoja->getColumnDimension('B')->setWidth(18);
        $hoja->getColumnDimension('C')->setWidth(7);
        $hoja->getColumnDimension('D')->setWidth(62);

        return $libro;
    }
}
