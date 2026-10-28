<?php

namespace App\Services;

use App\Models\Conteo;
use App\Models\ConteoMovimiento;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Conteo en Excel: todas sus tablas, una debajo de la otra, en el orden de la plantilla.
 */
class ConteoExcelService
{
    private const FORMATO_PESOS = '#,##0;[Red]-#,##0';

    public function __construct(private ConteoService $conteos) {}

    public function generar(Conteo $conteo): Spreadsheet
    {
        $conteo->load(['billetes', 'movimientos', 'consignaciones.turno', 'aplicaciones.destino', 'aplicaciones.tercero']);
        $resumen = $this->conteos->resumen($conteo);

        $libro = new Spreadsheet;
        $hoja = $libro->getActiveSheet();
        $hoja->setTitle('Conteo '.$conteo->numero);
        $hoja->setCellValue('A1', 'C O N T E O');
        $hoja->setCellValue('C1', 'FECHA: '.$conteo->fecha->format('d/m/Y'));
        $hoja->setCellValue('E1', 'No. '.$conteo->numero);
        $hoja->getStyle('A1:E1')->getFont()->setBold(true)->setSize(13);

        $fila = 3;
        $fila = $this->bloque($hoja, $fila, 'CUADRE DE EFECTIVO', ['BILLETE', 'VALOR'],
            $conteo->billetes->map(fn ($b) => [$b->denominacion, (float) $b->valor])->all(), [
                ['BILLETES', $resumen['billetes']],
                ['MONEDA', $resumen['moneda']],
                ['SUB TOTAL', $resumen['subtotal']],
                ['RETIROS', $resumen['retiros']],
                ['TOTAL CIERRE', $resumen['total_cierre']],
            ]);

        $fila = $this->bloque($hoja, $fila, 'RETIROS ANTES DE ESTE CIERRE', ['NOMBRE', 'CONCEPTO', 'VALOR'],
            $this->movimientos($conteo, ConteoMovimiento::TIPO_RETIRO, true), [['TOTAL RETIROS', '', $resumen['retiros']]]);

        $fila = $this->bloque($hoja, $fila, 'DETALLE DE NUMERACIÓN DE CONSIGNACIONES DE COMBUSTIBLE', ['No.', 'VALOR', 'PLANILLA'],
            [[$resumen['consignacion_anterior'], 'Último del conteo anterior', ''],
                ...$conteo->consignaciones->map(fn ($c) => [$c->numero, (float) $c->valor, $c->turno ? 'Turno #'.$c->turno->numero_turno : 'No está en ninguna planilla'])->all()], [
                    ['TOTAL CONSIGNACIONES INTERNAS', $resumen['total_consignaciones'], ''],
                    ['DIFERENCIA (TOTAL CIERRE - CONSIGNACIONES)', $resumen['diferencia'], ''],
                ]);

        $fila = $this->bloque($hoja, $fila, 'CUADRE DE ESTE CONTEO', ['CONCEPTO (GASTOS)', 'VALOR'],
            array_map(fn (array $m) => [$m[1], $m[2]], $this->movimientos($conteo, ConteoMovimiento::TIPO_GASTO, false)), [
                ['TOTAL BILLETES', $resumen['billetes']],
                ['TOTAL GASTOS', $resumen['gastos']],
                ['A CONSIGNAR', $resumen['a_consignar']],
            ]);

        $fila = $this->bloque($hoja, $fila, 'CONSIGNACIÓN EN BANCOS Y OTRAS APLICACIONES', ['DESTINO', 'TERCERO', 'VALOR'],
            $conteo->aplicaciones->map(fn ($a) => [$a->destino?->nombre, $a->tercero?->nombre, (float) $a->valor])->all(), [
                ['TOTAL CONSIGNACIONES Y OTROS', '', $resumen['aplicaciones']],
                ['DIFERENCIA NO CONSIGNADA', '', $resumen['no_consignado']],
            ]);

        $this->bloque($hoja, $fila, 'CONTROL DE MONEDA', ['RETIROS DE MONEDA (CONCEPTO)', 'VALOR'],
            array_map(fn (array $m) => [$m[1], $m[2]], $this->movimientos($conteo, ConteoMovimiento::TIPO_RETIRO_MONEDA, false)), [
                ['Saldo que viene del conteo anterior', $resumen['saldo_moneda_anterior']],
                ['Más: moneda del presente conteo', $resumen['moneda']],
                ['Menos: retiros', $resumen['retiros_moneda']],
                ['Saldo que pasa', $resumen['saldo_moneda_pasa']],
            ]);

        foreach (['A' => 40, 'B' => 30, 'C' => 22] as $columna => $ancho) {
            $hoja->getColumnDimension($columna)->setWidth($ancho);
        }

        return $libro;
    }

    /**
     * @return list<array{0: string|null, 1: string|null, 2: float}>
     */
    private function movimientos(Conteo $conteo, string $tipo, bool $conNombre): array
    {
        return $conteo->movimientos->where('tipo', $tipo)
            ->map(fn ($m) => [$conNombre ? $m->nombre : null, $m->concepto, (float) $m->valor])
            ->values()
            ->all();
    }

    /**
     * Título, encabezados, filas y totales (en negrilla). La última columna es la de valor.
     *
     * @param  list<string>  $encabezados
     * @param  list<list<mixed>>  $filas
     * @param  list<list<mixed>>  $totales
     */
    private function bloque(Worksheet $hoja, int $fila, string $titulo, array $encabezados, array $filas, array $totales): int
    {
        $ultima = chr(ord('A') + count($encabezados) - 1);

        $hoja->setCellValue('A'.$fila, $titulo);
        $hoja->getStyle("A{$fila}:{$ultima}{$fila}")->getFont()->setBold(true);
        $hoja->getStyle("A{$fila}:{$ultima}{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EEECE1');
        $fila++;

        $hoja->fromArray($encabezados, null, 'A'.$fila);
        $hoja->getStyle("A{$fila}:{$ultima}{$fila}")->getFont()->setBold(true);
        $inicio = $fila + 1;

        foreach ($filas as $datos) {
            $hoja->fromArray($datos, null, 'A'.(++$fila));
        }

        foreach ($totales as $datos) {
            $hoja->fromArray($datos, null, 'A'.(++$fila));
            $hoja->getStyle("A{$fila}:{$ultima}{$fila}")->getFont()->setBold(true);
        }

        $hoja->getStyle("B{$inicio}:{$ultima}{$fila}")->getNumberFormat()->setFormatCode(self::FORMATO_PESOS);

        return $fila + 2;
    }
}
