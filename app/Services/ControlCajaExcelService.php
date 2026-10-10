<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Informe Control Caja en Excel: una fila por planilla con las columnas (1) a (21) de la
 * plantilla y, debajo, el comprobante contable de ventas del periodo.
 */
class ControlCajaExcelService
{
    private const FORMATO_PESOS = '#,##0;[Red]-#,##0';

    public function __construct(private ControlCajaService $controlCaja) {}

    public function generar(string $desde, string $hasta): Spreadsheet
    {
        $informe = $this->controlCaja->informe($desde, $hasta);

        $libro = new Spreadsheet;
        $hoja = $libro->getActiveSheet();
        $hoja->setTitle('Control caja');

        $hoja->setCellValue('A1', 'INFORME CONTROL CAJA');
        $hoja->setCellValue('A2', 'DESDE '.Carbon::parse($desde)->format('d/m/Y').'  HASTA '.Carbon::parse($hasta)->format('d/m/Y'));
        $hoja->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('FF0000');

        $encabezados = [
            'FECHA', 'PLANILLA No.', 'GASOLINA', 'ACPM', 'CANASTILLA', 'IVA GENERADO', 'TOTAL VENTAS',
            'CONSIGNACIONES', 'TC, QR Y TRANSFERENCIAS', 'PUNTOS REDIMIDOS', 'GASOLINA EDS', 'DESCUENTOS', 'CARTERA CRED. DTO',
        ];
        foreach (range(1, ControlCajaService::CASILLAS_VARIOS) as $numero) {
            $encabezados[] = 'CONCEPTO No. '.$numero;
            $encabezados[] = 'VALOR No. '.$numero;
        }
        array_push($encabezados, 'FALTANTES', 'SOBRANTES', 'RECAUDOS Y ANTICIPOS', 'TOTAL', 'RECIBIDO S/PLANILLA', 'RECIBIDO S/LECTURA');

        $ultima = Coordinate::stringFromColumnIndex(count($encabezados));
        $hoja->fromArray($encabezados, null, 'A4');
        $encabezado = $hoja->getStyle("A4:{$ultima}4");
        $encabezado->getFont()->setBold(true);
        $encabezado->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setWrapText(true);
        $encabezado->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DDEBF7');

        $fila = 4;
        foreach ($informe['filas'] as $dato) {
            $fila++;
            $hoja->fromArray($this->valores($dato), null, 'A'.$fila);
        }

        $fila++;
        $totales = $informe['totales'];
        $hoja->fromArray($this->valores(array_merge($totales, [
            'fecha' => null,
            'numero_turno' => 'TOTALES',
            'varios' => array_map(fn (float $valor): array => ['concepto' => '', 'valor' => $valor], $totales['varios']),
        ])), null, 'A'.$fila);
        $hoja->getStyle("A{$fila}:{$ultima}{$fila}")->getFont()->setBold(true);

        $hoja->getStyle("C5:{$ultima}{$fila}")->getNumberFormat()->setFormatCode(self::FORMATO_PESOS);
        $hoja->getStyle("A4:{$ultima}{$fila}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Comprobante contable de ventas.
        $inicio = $fila + 3;
        $hoja->setCellValue("A{$inicio}", 'COMPROBANTE CONTABLE DE VENTAS');
        $hoja->getStyle("A{$inicio}")->getFont()->setBold(true);
        $hoja->fromArray(['CUENTA', 'CONCEPTO', 'DEBITO', 'CREDITO'], null, 'A'.($inicio + 1));
        $hoja->getStyle('A'.($inicio + 1).':D'.($inicio + 1))->getFont()->setBold(true);
        $hoja->getStyle('A'.($inicio + 1).':D'.($inicio + 1))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('CCCCFF');

        $fila = $inicio + 1;
        foreach ($informe['comprobante']['lineas'] as $linea) {
            $fila++;
            $hoja->setCellValueExplicit("A{$fila}", $linea['cuenta'], DataType::TYPE_STRING);
            $hoja->setCellValue("B{$fila}", $linea['concepto']);
            $hoja->setCellValue("C{$fila}", $linea['debito'] ?: null);
            $hoja->setCellValue("D{$fila}", $linea['credito'] ?: null);
        }
        $fila++;
        $hoja->fromArray(['', 'TOTALES', $informe['comprobante']['debito'], $informe['comprobante']['credito']], null, 'A'.$fila);
        $hoja->fromArray(['', 'DIFERENCIA', $informe['comprobante']['diferencia']], null, 'A'.($fila + 1));
        $hoja->getStyle("A{$fila}:D".($fila + 1))->getFont()->setBold(true);
        $hoja->getStyle('C'.($inicio + 2).':D'.($fila + 1))->getNumberFormat()->setFormatCode(self::FORMATO_PESOS);
        $hoja->getStyle('A'.($inicio + 1).":D{$fila}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        foreach (range(1, count($encabezados)) as $columna) {
            $hoja->getColumnDimension(Coordinate::stringFromColumnIndex($columna))->setWidth(14);
        }
        $hoja->getColumnDimension('B')->setWidth(36);

        return $libro;
    }

    /**
     * @param  array<string, mixed>  $dato
     * @return list<mixed>
     */
    private function valores(array $dato): array
    {
        $valores = [
            $dato['fecha'] ? Carbon::parse($dato['fecha'])->format('d/m/Y') : null,
            $dato['numero_turno'],
            $dato['gasolina'], $dato['acpm'], $dato['canastilla'], $dato['iva'], $dato['total_ventas'],
            $dato['consignaciones'], $dato['pagos_electronicos'], $dato['puntos'], $dato['gasolina_eds'], $dato['descuentos'], $dato['cartera'],
        ];

        foreach ($dato['varios'] as $vario) {
            $valores[] = $vario['concepto'];
            $valores[] = $vario['valor'];
        }

        return [...$valores,
            $dato['faltantes'], $dato['sobrantes'], $dato['recaudos'], $dato['total'],
            $dato['recibido_planilla'], $dato['recibido_lectura'],
        ];
    }
}
