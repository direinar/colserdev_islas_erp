<?php

namespace App\Services;

use App\Models\CarteraMovimiento;
use App\Models\Customer;
use App\Support\NumberParser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Throwable;

/**
 * Saldos iniciales de cartera: el saldo que cada cliente trae al arrancar el
 * sistema (ej. al 31 de octubre). Se cargan una sola vez, por CRUD, archivo
 * Excel o seeder, y entran al SALDO ANTERIOR de los estados de cuenta.
 *
 * Valor positivo = el cliente debe (cargo); negativo = saldo a favor o
 * anticipo (abono). Hay un solo saldo inicial por cliente.
 */
class CarteraSaldoInicialService
{
    public const CONCEPTO = 'Saldo inicial';

    public function __construct(private CarteraSaldoService $saldos) {}

    public function registrar(int $customerId, string $fechaCorte, float $valor): CarteraMovimiento
    {
        $valor = round($valor, 0);

        return DB::transaction(function () use ($customerId, $fechaCorte, $valor): CarteraMovimiento {
            $movimiento = CarteraMovimiento::query()->updateOrCreate(
                ['customer_id' => $customerId, 'origen' => CarteraMovimiento::ORIGEN_SALDO_INICIAL],
                [
                    'fecha' => $fechaCorte,
                    'saldo_inicial' => $valor,
                    'planillas' => '',
                    'factura' => '',
                    'concepto' => self::CONCEPTO,
                    'bruto' => 0,
                    'vr_neto_cargo' => max(0, $valor),
                    'abonos' => max(0, -$valor),
                ]
            );

            $this->saldos->recalcular($customerId);

            return $movimiento;
        });
    }

    public function eliminar(CarteraMovimiento $movimiento): void
    {
        DB::transaction(function () use ($movimiento): void {
            $movimiento->delete();
            $this->saldos->recalcular((int) $movimiento->customer_id);
        });
    }

    /**
     * Importa un Excel con encabezado en la primera fila y las columnas:
     * A = NIT / documento del cliente, B = fecha de corte, C = valor.
     *
     * @return array{cargados: int, errores: array<int, string>}
     */
    public function importar(string $rutaArchivo): array
    {
        $hoja = IOFactory::load($rutaArchivo)->getActiveSheet();
        $clientes = Customer::all();
        $cargados = 0;
        $errores = [];

        foreach ($hoja->toArray(null, false, false, true) as $numeroFila => $fila) {
            if ($numeroFila === 1) {
                continue;
            }

            $documento = trim((string) ($fila['A'] ?? ''));
            $fecha = $fila['B'] ?? null;
            $valor = $fila['C'] ?? null;

            // Fila sin fecha ni valor: cliente de la plantilla que no trae saldo.
            if (($fecha === null || $fecha === '') && ($valor === null || $valor === '')) {
                continue;
            }

            $cliente = $this->buscarCliente($clientes, $documento);

            if (! $cliente) {
                $errores[] = "Fila {$numeroFila}: no existe un cliente con NIT/documento \"{$documento}\".";

                continue;
            }

            $fechaCorte = $this->fecha($fecha);

            if (! $fechaCorte) {
                $errores[] = "Fila {$numeroFila}: la fecha de corte no es válida.";

                continue;
            }

            if ($valor === null || $valor === '') {
                $errores[] = "Fila {$numeroFila}: falta el valor del saldo.";

                continue;
            }

            $this->registrar($cliente->id, $fechaCorte, $this->valor($valor));
            $cargados++;
        }

        return ['cargados' => $cargados, 'errores' => $errores];
    }

    /**
     * Plantilla para la importación: todos los clientes con su NIT; se llenan
     * fecha de corte y valor solo de los que tienen saldo. La columna D es de
     * referencia y no se lee al importar.
     */
    public function plantilla(): Spreadsheet
    {
        $libro = new Spreadsheet;
        $hoja = $libro->getActiveSheet();
        $hoja->setTitle('Saldos iniciales');

        $filas = [['NIT / DOCUMENTO', 'FECHA DE CORTE (aaaa-mm-dd)', 'VALOR (negativo = a favor)', 'CLIENTE (referencia)']];

        foreach (Customer::orderBy('name')->get() as $cliente) {
            $filas[] = [(string) $cliente->document, null, null, $cliente->name];
        }

        $hoja->fromArray($filas);
        $hoja->getStyle('A1:D1')->getFont()->setBold(true);
        $hoja->getStyle('A1:D1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('CCCCFF');
        $hoja->getStyle('A2:A'.count($filas))->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        $hoja->getStyle('C2:C'.count($filas))->getNumberFormat()->setFormatCode('#,##0;[Red]-#,##0');

        foreach (['A', 'B', 'C', 'D'] as $columna) {
            $hoja->getColumnDimension($columna)->setAutoSize(true);
        }

        return $libro;
    }

    /**
     * Una celda numérica de Excel ya trae el número. El texto se lee como
     * dinero es-CO sin decimales ("1.250.000") y admite signo negativo
     * (saldo a favor del cliente).
     */
    public function valor(mixed $valor): float
    {
        if (is_int($valor) || is_float($valor)) {
            return (float) $valor;
        }

        $texto = trim((string) $valor);
        $numero = NumberParser::money(ltrim($texto, '-'));

        return str_starts_with($texto, '-') ? -$numero : $numero;
    }

    /**
     * @param  Collection<int, Customer>  $clientes
     */
    private function buscarCliente(Collection $clientes, string $documento): ?Customer
    {
        if ($documento === '') {
            return null;
        }

        $normalizar = fn (?string $valor): string => preg_replace('/[.\s]/', '', trim((string) $valor));

        return $clientes->first(fn (Customer $c): bool => trim((string) $c->document) === $documento)
            ?? $clientes->first(fn (Customer $c): bool => $normalizar($c->document) === $normalizar($documento));
    }

    private function fecha(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (is_numeric($valor)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $valor))->toDateString();
            } catch (Throwable) {
                return null;
            }
        }

        $texto = trim((string) $valor);

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $formato) {
            try {
                $fecha = Carbon::createFromFormat('!'.$formato, $texto);
            } catch (Throwable) {
                continue;
            }

            if ($fecha && $fecha->format($formato) === $texto) {
                return $fecha->toDateString();
            }
        }

        return null;
    }
}
