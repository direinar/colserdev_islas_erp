<?php

namespace App\Services;

use App\Models\AforoTanque;
use App\Models\Compra;
use App\Models\TanqueSaldoInicial;
use App\Models\Turno;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Informe de control de tanques, solo en galones. Ingresos y ventas no se digitan:
 * el ingreso sale de compras y la venta de los galones según cortes de IAPROPIADA
 * de la planilla de turnos (suma de los turnos de cada fecha), no de las lecturas
 * de los surtidores. Lo único que se registra aquí es el aforo nocturno del jefe de patios.
 *
 * Saldo = saldo anterior + ingreso - venta, arrancando del saldo inicial del tanque.
 * Faltante/Sobrante = saldo del movimiento de galones - saldo final según tabla de aforo
 * (positivo = sobrante; negativo = FALTANTE, en rojo).
 */
class ControlTanquesService
{
    /**
     * Columna de galones en compras y en la planilla de turnos, por combustible.
     *
     * @var array<string, array{compra: string, venta: string}>
     */
    private const COLUMNAS = [
        TanqueSaldoInicial::CORRIENTE => ['compra' => 'gasolina', 'venta' => 'tirillas_galones_corriente'],
        TanqueSaldoInicial::ACPM => ['compra' => 'acpm', 'venta' => 'tirillas_galones_acpm'],
    ];

    /**
     * @return array{
     *     vienen: array<string, float|null>,
     *     filas: list<array<string, mixed>>,
     *     totales: array<string, float>,
     *     saldosIniciales: Collection<string, TanqueSaldoInicial>
     * }
     */
    public function informe(string $desde, string $hasta): array
    {
        $saldosIniciales = TanqueSaldoInicial::query()->get()->keyBy('combustible');

        $ingresos = $this->porFecha(
            Compra::query()->whereDate('fecha', '>=', $desde)->whereDate('fecha', '<=', $hasta)->get(),
            'compra',
        );
        $ventas = $this->porFecha(
            Turno::query()->whereDate('fecha', '>=', $desde)->whereDate('fecha', '<=', $hasta)->get(),
            'venta',
        );
        $aforos = AforoTanque::query()
            ->whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hasta)
            ->get()
            ->keyBy(fn (AforoTanque $aforo): string => $aforo->fecha->format('Y-m-d'));

        $vienen = [];
        $saldos = [];
        foreach (array_keys(self::COLUMNAS) as $combustible) {
            $vienen[$combustible] = $this->vienen($combustible, $saldosIniciales->get($combustible), $desde);
            $saldos[$combustible] = $vienen[$combustible];
        }

        $totales = [
            'ingreso_corriente' => 0.0,
            'venta_corriente' => 0.0,
            'ingreso_acpm' => 0.0,
            'venta_acpm' => 0.0,
            'venta_diaria' => 0.0,
        ];

        $acumulada = 0.0;
        $filas = [];

        for ($dia = Carbon::parse($desde); $dia->lte(Carbon::parse($hasta)); $dia->addDay()) {
            $fecha = $dia->format('Y-m-d');
            $aforo = $aforos->get($fecha);
            $fila = ['fecha' => $fecha];

            foreach (array_keys(self::COLUMNAS) as $combustible) {
                $ingreso = $ingresos[$fecha][$combustible] ?? 0.0;
                $venta = $ventas[$fecha][$combustible] ?? 0.0;
                $saldoInicial = $saldosIniciales->get($combustible);

                // Si el informe empieza antes del corte, el saldo arranca el día del corte.
                if ($saldos[$combustible] === null && $saldoInicial?->fecha_corte->format('Y-m-d') === $fecha) {
                    $saldos[$combustible] = (float) $saldoInicial->galones;
                }

                if ($saldos[$combustible] !== null) {
                    $saldos[$combustible] += $ingreso - $venta;
                }

                $vara = $aforo?->{'vara_'.$combustible};
                $tabla = $aforo?->{'tabla_'.$combustible};

                $fila[$combustible] = [
                    'ingreso' => $ingreso,
                    'venta' => $venta,
                    'saldo' => $saldos[$combustible],
                    'vara' => $vara !== null ? (float) $vara : null,
                    'tabla' => $tabla !== null ? (float) $tabla : null,
                    'faltante_sobrante' => $tabla !== null && $saldos[$combustible] !== null
                        ? $saldos[$combustible] - (float) $tabla
                        : null,
                ];

                $totales['ingreso_'.$combustible] += $ingreso;
                $totales['venta_'.$combustible] += $venta;
            }

            $ventaDiaria = $fila[TanqueSaldoInicial::CORRIENTE]['venta'] + $fila[TanqueSaldoInicial::ACPM]['venta'];
            $acumulada += $ventaDiaria;
            $totales['venta_diaria'] += $ventaDiaria;

            $filas[] = $fila + ['venta_diaria' => $ventaDiaria, 'venta_acumulada' => $acumulada];
        }

        return [
            'vienen' => $vienen,
            'filas' => $filas,
            'totales' => $totales,
            'saldosIniciales' => $saldosIniciales,
        ];
    }

    /**
     * Saldo del tanque al iniciar el día $desde. Sin saldo inicial registrado se acumula
     * toda la historia desde cero; si $desde es anterior al corte, aún no hay saldo (null).
     */
    private function vienen(string $combustible, ?TanqueSaldoInicial $saldoInicial, string $desde): ?float
    {
        $corte = $saldoInicial?->fecha_corte->format('Y-m-d');

        if ($corte !== null && $desde <= $corte) {
            return $desde === $corte ? (float) $saldoInicial->galones : null;
        }

        $antesDe = fn ($query) => $query
            ->whereDate('fecha', '<', $desde)
            ->when($corte, fn ($query) => $query->whereDate('fecha', '>=', $corte));

        $ingresos = (float) $antesDe(Compra::query())->sum(self::COLUMNAS[$combustible]['compra']);
        $ventas = (float) $antesDe(Turno::query())->sum(self::COLUMNAS[$combustible]['venta']);

        return (float) ($saldoInicial?->galones ?? 0) + $ingresos - $ventas;
    }

    /**
     * Suma los galones de cada combustible por fecha.
     *
     * @param  Collection<int, Compra|Turno>  $registros
     * @return array<string, array<string, float>>
     */
    private function porFecha(Collection $registros, string $tipo): array
    {
        $porFecha = [];

        foreach ($registros as $registro) {
            $fecha = $registro->fecha->format('Y-m-d');

            foreach (self::COLUMNAS as $combustible => $columnas) {
                $porFecha[$fecha][$combustible] = ($porFecha[$fecha][$combustible] ?? 0.0) + (float) $registro->{$columnas[$tipo]};
            }
        }

        return $porFecha;
    }
}
