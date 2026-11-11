<?php

namespace App\Services;

use App\Models\Turno;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Planilla de turno guardada para exportarla a PDF y a Excel con el mismo contenido de la
 * pantalla: encabezado, ventas según cierres de IAPROPIADA, canastilla, cada medio de pago
 * y el resumen de lo recibido con el sobrante/faltante (fórmula de CuadreTurnoService).
 */
class PlanillaTurnoExportService
{
    private const FORMATO_PESOS = '#,##0;[Red]-#,##0';

    private const FORMATO_GALONES = '#,##0.000;[Red]-#,##0.000';

    public function __construct(private CuadreTurnoService $cuadre) {}

    /**
     * Las secciones con filas; una sección sin filas no se muestra. En cada columna, el
     * formato indica cómo se escribe el valor: texto, pesos o galones.
     *
     * @return list<array{titulo: string, columnas: list<array{titulo: string, formato: string}>, filas: list<list<mixed>>, total: list<mixed>|null}>
     */
    public function secciones(Turno $turno): array
    {
        $turno->loadMissing([
            ...CuadreTurnoService::RELACIONES_RECIBIDO,
            ...CuadreTurnoService::RELACIONES_VENTAS,
            'cartera.cliente', 'recaudos.cliente', 'recaudosAdmin.responsable',
        ]);

        $texto = fn (string $titulo): array => ['titulo' => $titulo, 'formato' => 'texto'];
        $pesos = fn (string $titulo): array => ['titulo' => $titulo, 'formato' => 'pesos'];
        $galones = fn (string $titulo): array => ['titulo' => $titulo, 'formato' => 'galones'];

        $ventas = $turno->ventas->sortBy([['surtidor', 'asc'], ['id', 'asc']]);

        $secciones = [
            [
                'titulo' => 'VENTAS SEGÚN CIERRES DE IAPPROPIADA',
                'columnas' => [$texto('SURTIDOR'), $texto('NO. CIERRE'), $galones('GALONES'), $pesos('VALOR')],
                'filas' => $ventas->map(fn ($venta): array => [
                    $venta->surtidor, (string) $venta->no_cierre, (float) $venta->galones, round((float) $venta->valor),
                ])->values()->all(),
                'total' => ['TOTAL', '', (float) $ventas->sum('galones'), (float) $ventas->sum(fn ($venta): float => round((float) $venta->valor))],
            ],
            [
                'titulo' => 'TOTAL VENTAS SEGÚN CORTE',
                'columnas' => [$texto('COMBUSTIBLE'), $galones('GALONES'), $pesos('VALOR')],
                'filas' => collect(['CTE' => 'CORRIENTE', 'ACPM' => 'ACPM'])->map(function (string $nombre, string $combustible) use ($ventas): array {
                    $delCombustible = $ventas->where('combustible', $combustible);

                    return [$nombre, (float) $delCombustible->sum('galones'), (float) $delCombustible->sum(fn ($venta): float => round((float) $venta->valor))];
                })->values()->all(),
                'total' => null,
            ],
            [
                'titulo' => 'VENTA DE CANASTILLA - CONTADO Y CRÉDITO',
                'columnas' => [$galones('CANT.'), $texto('PRODUCTO'), $pesos('VR. SIN IVA'), $pesos('IVA'), $pesos('TOTAL')],
                'filas' => $turno->lubricantes->map(fn ($lubricante): array => [
                    (float) $lubricante->cantidad, (string) $lubricante->producto, (float) $lubricante->valor_sin_iva, (float) $lubricante->iva, (float) $lubricante->total,
                ])->values()->all(),
                'total' => ['TOTAL', '', (float) $turno->lubricantes->sum('valor_sin_iva'), (float) $turno->lubricantes->sum('iva'), (float) $turno->lubricantes->sum('total')],
            ],
            $this->lista('CONSIGNACIONES', [$texto('No.'), $pesos('VALOR')], $turno->consignaciones->map(fn ($c): array => [(string) $c->consignacion_no, (float) $c->valor])),
            $this->lista('DESCUENTOS', [$texto('No.'), $pesos('VALOR')], $turno->descuentos->map(fn ($d): array => [(string) $d->descuento_no, (float) $d->valor])),
            $this->lista('PAGOS ELECTRÓNICOS Y TRANSFERENCIAS', [$texto('CONCEPTO'), $pesos('VALOR')], $turno->qrPagos->map(fn ($q): array => [(string) $q->concepto, (float) $q->valor])),
            $this->lista('CARTERA - CRÉDITO DIRECTO', [$texto('No. FACTURA'), $texto('CLIENTE'), $pesos('VALOR')], $turno->cartera->map(fn ($c): array => [(string) $c->factura_no, (string) $c->cliente?->name, (float) $c->valor])),
            $this->lista('PUNTOS REDIMIDOS', [$pesos('VALOR')], $turno->transferencias->map(fn ($t): array => [(float) $t->puntos_redimidos])),
            $this->lista('RECAUDOS Y ANTICIPOS POR ISLAS', [$texto('CLIENTE'), $pesos('VALOR')], $turno->recaudos->map(fn ($r): array => [(string) $r->cliente?->name, (float) $r->valor])),
            $this->lista('GASOLINA EDS', [$pesos('VALOR')], $turno->gasolinaEds->map(fn ($g): array => [(float) $g->valor])),
            $this->lista('VARIOS', [$texto('CONCEPTO'), $pesos('VALOR')], $turno->varios->map(fn ($v): array => [(string) $v->concepto, (float) $v->valor])),
            $this->lista('RECAUDOS POR ADMINISTRACIÓN', [$texto('BANCO/CAJA'), $texto('CLIENTE'), $pesos('VALOR')], $turno->recaudosAdmin->map(fn ($r): array => [(string) $r->banco, (string) $r->responsable?->name, (float) $r->valor])),
            [
                'titulo' => 'RESUMEN DE LO RECIBIDO EN ESTE TURNO',
                'columnas' => [$texto('CONCEPTO'), $pesos('VALOR')],
                'filas' => $this->resumen($turno),
                'total' => null,
            ],
        ];

        return array_values(array_filter($secciones, fn (array $seccion): bool => $seccion['filas'] !== []));
    }

    /**
     * Fecha, turno, vendedor, precios y estado de la planilla.
     *
     * @return list<array{0: string, 1: string}>
     */
    public function encabezado(Turno $turno): array
    {
        $estado = match (true) {
            (bool) $turno->anulada => 'ANULADA'.($turno->motivo_anulacion ? ' — '.$turno->motivo_anulacion : ''),
            (bool) $turno->revisado => 'REVISADA por '.$turno->revisado_por.($turno->revisado_at ? ' el '.$turno->revisado_at->format('d/m/Y H:i') : ''),
            default => 'PENDIENTE DE REVISIÓN',
        };

        return [
            ['FECHA', $turno->fecha->format('d/m/Y')],
            ['TURNO', str_pad((string) $turno->numero_turno, 3, '0', STR_PAD_LEFT)],
            ['VENDEDOR', (string) $turno->nombre_vendedor],
            ['PRECIO CORRIENTE', number_format((float) $turno->precio_corriente, 0, ',', '.')],
            ['PRECIO ACPM', number_format((float) $turno->precio_acpm, 0, ',', '.')],
            ['ESTADO', $estado],
            ['OBSERVACIONES', (string) $turno->observaciones],
        ];
    }

    /**
     * Toda la planilla en una hoja: el encabezado arriba y cada sección debajo de la anterior.
     */
    public function excel(Turno $turno): Spreadsheet
    {
        $libro = new Spreadsheet;
        $hoja = $libro->getActiveSheet();
        $hoja->setTitle('Turno '.$turno->numero_turno);

        $hoja->setCellValue('A1', 'PLANILLA DE TURNO '.str_pad((string) $turno->numero_turno, 3, '0', STR_PAD_LEFT));
        $hoja->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('FF0000');

        $fila = 2;
        foreach ($this->encabezado($turno) as [$campo, $valor]) {
            $fila++;
            $hoja->setCellValue('A'.$fila, $campo);
            $hoja->setCellValueExplicit('B'.$fila, $valor, DataType::TYPE_STRING);
            $hoja->getStyle('A'.$fila)->getFont()->setBold(true);
        }

        foreach ($this->secciones($turno) as $seccion) {
            $fila += 2;
            $ultima = chr(ord('A') + count($seccion['columnas']) - 1);

            $hoja->setCellValue('A'.$fila, $seccion['titulo']);
            $hoja->getStyle('A'.$fila)->getFont()->setBold(true);
            $hoja->getStyle("A{$fila}:{$ultima}{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9D6F9');

            $fila++;
            $inicio = $fila;
            $hoja->fromArray(array_column($seccion['columnas'], 'titulo'), null, 'A'.$fila);
            $hoja->getStyle("A{$fila}:{$ultima}{$fila}")->getFont()->setBold(true);

            $renglones = $seccion['filas'];
            if ($seccion['total'] !== null) {
                $renglones[] = $seccion['total'];
            }

            foreach ($renglones as $renglon) {
                $fila++;
                foreach (array_values($renglon) as $indice => $valor) {
                    $celda = chr(ord('A') + $indice).$fila;
                    $formato = $seccion['columnas'][$indice]['formato'];

                    if ($formato === 'texto' || ! is_numeric($valor)) {
                        $hoja->setCellValueExplicit($celda, (string) $valor, DataType::TYPE_STRING);

                        continue;
                    }

                    $hoja->setCellValue($celda, $valor);
                    $hoja->getStyle($celda)->getNumberFormat()->setFormatCode($formato === 'galones' ? self::FORMATO_GALONES : self::FORMATO_PESOS);
                }
            }

            if ($seccion['total'] !== null) {
                $hoja->getStyle("A{$fila}:{$ultima}{$fila}")->getFont()->setBold(true);
            }
            $hoja->getStyle("A{$inicio}:{$ultima}{$fila}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }

        foreach (range('A', 'E') as $columna) {
            $hoja->getColumnDimension($columna)->setAutoSize(true);
        }

        return $libro;
    }

    /**
     * Sección de un medio de pago con su total en la última columna.
     *
     * @param  list<array{titulo: string, formato: string}>  $columnas
     * @param  Collection<int, list<mixed>>  $filas
     * @return array{titulo: string, columnas: list<array{titulo: string, formato: string}>, filas: list<list<mixed>>, total: list<mixed>}
     */
    private function lista(string $titulo, array $columnas, Collection $filas): array
    {
        $total = array_fill(0, count($columnas), '');
        $total[0] = 'TOTAL';
        $total[count($columnas) - 1] = (float) $filas->sum(fn (array $fila): float => (float) end($fila));

        return ['titulo' => $titulo, 'columnas' => $columnas, 'filas' => $filas->values()->all(), 'total' => $total];
    }

    /**
     * Las mismas líneas del recuadro RESUMEN DE LO RECIBIDO EN ESTE TURNO.
     *
     * @return list<array{0: string, 1: float}>
     */
    private function resumen(Turno $turno): array
    {
        $recaudos = (float) $turno->recaudos->sum('valor');
        $recibido = $this->cuadre->totalRecibido($turno);
        $ventas = $this->cuadre->ventasIapropiada($turno);

        return [
            ['CONSIGNACIONES', (float) $turno->consignaciones->sum('valor')],
            ['PAGOS ELECTRÓNICOS Y TRANSFERENCIAS', (float) $turno->qrPagos->sum('valor')],
            ['PUNTOS REDIMIDOS', (float) $turno->transferencias->sum('puntos_redimidos')],
            ['GASOLINA EDS', (float) $turno->gasolinaEds->sum('valor')],
            ['DESCUENTOS', (float) $turno->descuentos->sum('valor')],
            ['CARTERA - CRÉDITO DIRECTO', (float) $turno->cartera->sum('valor')],
            ['VARIOS', (float) $turno->varios->sum('valor')],
            ['SOBRANTE TRASLADADO', (float) $turno->traslado_sobrante],
            ['FALTANTE TRASLADADO', (float) $turno->traslado_faltante],
            ['SUBTOTAL INGRESOS', $recibido + $recaudos],
            ['RECAUDOS Y ANTICIPOS', $recaudos],
            ['TOTAL RECIBIDO EN ESTE TURNO', $recibido],
            ['VENTAS IAPPROPIADA (COMBUSTIBLE + CANASTILLA)', $ventas],
            ['SOBRANTE O FALTANTE SEGÚN CIERRES DE IAPPROPIADA', $this->cuadre->pendiente($turno)],
        ];
    }
}
