<?php

namespace App\Services;

use App\Models\TanqueSaldoInicial;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Informe de control de tanques en Excel con la misma distribución de la plantilla
 * "INFORME DE CONTROL TANQUES": bloque CORRIENTE, bloque ACPM y venta diaria/acumulada.
 */
class ControlTanquesExcelService
{
    private const FORMATO_GALONES = '#,##0.00;[Red]-#,##0.00';

    /**
     * @var array<string, string>
     */
    private const COLORES = [
        'B:D' => 'E2EFDA',
        'E:F' => 'BFBFBF',
        'G:G' => 'E2EFDA',
        'H:J' => 'DCEFD4',
        'K:L' => 'CDE8C0',
        'M:M' => 'DCEFD4',
        'N:O' => 'D6DCE4',
    ];

    public function __construct(private ControlTanquesService $controlTanques) {}

    public function generar(string $desde, string $hasta): Spreadsheet
    {
        $informe = $this->controlTanques->informe($desde, $hasta);

        $libro = new Spreadsheet;
        $hoja = $libro->getActiveSheet();
        $hoja->setTitle('Control de tanques');

        $hoja->setCellValue('A1', 'INFORME DE CONTROL DE TANQUES');
        $hoja->setCellValue('A2', 'Periodo: '.Carbon::parse($desde)->format('d/m/Y').' al '.Carbon::parse($hasta)->format('d/m/Y'));
        $hoja->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $hoja->mergeCells('B4:G4')->setCellValue('B4', 'CORRIENTE');
        $hoja->mergeCells('H4:M4')->setCellValue('H4', 'A C P M');
        $hoja->mergeCells('N4:O4')->setCellValue('N4', 'SALDOS');
        $hoja->fromArray([
            'FECHA DE VENTA',
            'INGRESO', 'VENTA', 'SALDO', 'AFORO S/VARA', 'SALDO FINAL S/TABLA AFORO', 'FALTANTE/SOBRANTE',
            'INGRESO', 'VENTA', 'SALDO', 'AFORO S/VARA', 'SALDO FINAL S/TABLA AFORO', 'FALTANTE/SOBRANTE',
            'VR. VENTA DIARIA', 'VR. VENTA ACUMULADA',
        ], null, 'A5');

        $hoja->getStyle('A1')->getFont()->getColor()->setRGB('FF0000');
        $encabezado = $hoja->getStyle('A4:O5');
        $encabezado->getFont()->setBold(true);
        $encabezado->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setWrapText(true);
        $hoja->getStyle('B4:O4')->getFont()->setSize(14);
        $hoja->getStyle('G5')->getFont()->getColor()->setRGB('FF0000');
        $hoja->getStyle('M5')->getFont()->getColor()->setRGB('FF0000');

        $hoja->fromArray([
            'VIENEN', null, null, $informe['vienen'][TanqueSaldoInicial::CORRIENTE], null, null, null,
            null, null, $informe['vienen'][TanqueSaldoInicial::ACPM], null, null, null, null, 0,
        ], null, 'A6');
        $hoja->getStyle('A6:O6')->getFont()->setBold(true);

        $fila = 6;
        foreach ($informe['filas'] as $dato) {
            $fila++;
            $hoja->fromArray([
                Carbon::parse($dato['fecha'])->format('d/m/Y'),
                ...$this->bloque($dato[TanqueSaldoInicial::CORRIENTE]),
                ...$this->bloque($dato[TanqueSaldoInicial::ACPM]),
                $dato['venta_diaria'],
                $dato['venta_acumulada'],
            ], null, 'A'.$fila);
        }

        $fila++;
        $totales = $informe['totales'];
        $hoja->fromArray([
            'TOTALES',
            $totales['ingreso_corriente'], $totales['venta_corriente'], null, null, null, null,
            $totales['ingreso_acpm'], $totales['venta_acpm'], null, null, null, null,
            $totales['venta_diaria'], null,
        ], null, 'A'.$fila);
        $hoja->getStyle("A{$fila}:O{$fila}")->getFont()->setBold(true);

        $hoja->getStyle("B6:O{$fila}")->getNumberFormat()->setFormatCode(self::FORMATO_GALONES);

        // Mismos colores por bloque que la plantilla: movimiento, aforo y saldos.
        foreach (self::COLORES as $rango => $color) {
            [$desdeColumna, $hastaColumna] = explode(':', $rango);
            $hoja->getStyle("{$desdeColumna}4:{$hastaColumna}{$fila}")->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($color);
        }

        $hoja->getStyle("A4:O{$fila}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        foreach (['B', 'E', 'H', 'K', 'N'] as $columna) {
            $hoja->getStyle("{$columna}4:{$columna}{$fila}")->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THICK);
        }
        $hoja->getColumnDimension('A')->setWidth(14);
        foreach (range('B', 'O') as $columna) {
            $hoja->getColumnDimension($columna)->setWidth(13);
        }

        $hoja->setCellValue('A'.($fila + 2), 'ESTE INFORME SOLO CONTIENE GALONES');

        return $libro;
    }

    /**
     * @param  array<string, float|null>  $combustible
     * @return list<float|null>
     */
    private function bloque(array $combustible): array
    {
        return [
            $combustible['ingreso'] ?: null,
            $combustible['venta'] ?: null,
            $combustible['saldo'],
            $combustible['vara'],
            $combustible['tabla'],
            $combustible['faltante_sobrante'],
        ];
    }
}
