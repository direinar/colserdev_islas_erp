<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Informe Control Caja en Excel: una fila por planilla con las columnas (1) a (21) de la
 * plantilla. El comprobante contable de ventas se exporta en ComprobanteVentasExcelService.
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
        array_push($encabezados, 'FALTANTES', 'SOBRANTES', 'ISLERO', 'RECAUDOS Y ANTICIPOS', 'TOTAL', 'RECIBIDO S/PLANILLA', 'RECIBIDO S/LECTURA');

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
            'islero' => null,
            'varios' => array_map(fn (float $valor): array => ['concepto' => '', 'valor' => $valor], $totales['varios']),
        ])), null, 'A'.$fila);
        $hoja->getStyle("A{$fila}:{$ultima}{$fila}")->getFont()->setBold(true);

        $hoja->getStyle("C5:{$ultima}{$fila}")->getNumberFormat()->setFormatCode(self::FORMATO_PESOS);
        $hoja->getStyle("A4:{$ultima}{$fila}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        foreach (range(1, count($encabezados)) as $columna) {
            $hoja->getColumnDimension(Coordinate::stringFromColumnIndex($columna))->setWidth(14);
        }
        $hoja->getColumnDimension(Coordinate::stringFromColumnIndex(array_search('ISLERO', $encabezados, true) + 1))->setWidth(24);

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
            $dato['faltantes'], $dato['sobrantes'], $dato['islero'], $dato['recaudos'], $dato['total'],
            $dato['recibido_planilla'], $dato['recibido_lectura'],
        ];
    }
}
