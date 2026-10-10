<?php

namespace App\Services;

use App\Models\Banco;
use App\Models\ComprobanteRecaudoCuenta;
use App\Models\TurnoRecaudo;
use App\Models\TurnoRecaudoAdmin;
use Illuminate\Support\Collection;

/**
 * Informe de recaudos de cartera (antes "planilla de administración") y su comprobante
 * contable. Sale de las planillas de turno: recaudos por islas (siempre en caja) y
 * recaudos por administración (en el banco o caja elegido).
 *
 * Comprobante: débito a caja por los recaudos por islas y a cada banco por los de
 * administración; crédito a la cartera del cliente partida por partida.
 */
class RecaudosCarteraService
{
    public const CONCEPTO_ISLAS = 'Recaudo por Islas';

    public const CONCEPTO_ADMIN = 'Recaudo por Admón';

    /**
     * @return array{
     *     recaudos: list<array{planilla: int, fecha: string, cliente: string, banco: string, concepto: string, valor: float}>,
     *     total: float,
     *     comprobante: array{lineas: list<array{cuenta: string, nombre: string, debito: float, credito: float}>, debito: float, credito: float, diferencia: float},
     *     cuentas: Collection<string, ComprobanteRecaudoCuenta>,
     *     bancos: Collection<int, Banco>
     * }
     */
    public function informe(string $desde, string $hasta): array
    {
        $enPeriodo = fn ($query) => $query->whereHas('turno', fn ($turno) => $turno
            ->whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hasta));

        $islas = $enPeriodo(TurnoRecaudo::query())
            ->with(['turno', 'cliente'])
            ->get()
            ->map(fn (TurnoRecaudo $recaudo): array => [
                'planilla' => (int) $recaudo->turno->numero_turno,
                'fecha' => $recaudo->turno->fecha->format('Y-m-d'),
                'cliente' => (string) ($recaudo->cliente?->name ?? 'SIN CLIENTE'),
                'banco' => 'CAJA',
                'concepto' => self::CONCEPTO_ISLAS,
                'valor' => (float) $recaudo->valor,
                'orden' => $recaudo->id,
            ]);

        $administracion = $enPeriodo(TurnoRecaudoAdmin::query())
            ->with(['turno', 'responsable'])
            ->get()
            ->map(fn (TurnoRecaudoAdmin $recaudo): array => [
                'planilla' => (int) $recaudo->turno->numero_turno,
                'fecha' => $recaudo->turno->fecha->format('Y-m-d'),
                'cliente' => (string) ($recaudo->responsable?->name ?? 'SIN CLIENTE'),
                'banco' => trim((string) $recaudo->banco) !== '' ? trim((string) $recaudo->banco) : 'SIN BANCO',
                'concepto' => self::CONCEPTO_ADMIN,
                'valor' => (float) $recaudo->valor,
                'orden' => $recaudo->id,
            ]);

        // Por planilla y fecha; dentro de cada planilla, primero los de islas.
        $recaudos = $islas->concat($administracion)
            ->sortBy([
                ['fecha', 'asc'],
                ['planilla', 'asc'],
                fn (array $a, array $b): int => [$a['concepto'] !== self::CONCEPTO_ISLAS, $a['orden']] <=> [$b['concepto'] !== self::CONCEPTO_ISLAS, $b['orden']],
            ])
            ->map(function (array $recaudo): array {
                unset($recaudo['orden']);

                return $recaudo;
            })
            ->values();

        $cuentas = ComprobanteRecaudoCuenta::query()->get()->keyBy('clave');
        $bancos = Banco::query()->orderBy('name')->get();

        return [
            'recaudos' => $recaudos->all(),
            'total' => (float) $recaudos->sum('valor'),
            'comprobante' => $this->comprobante($recaudos, $cuentas, $bancos),
            'cuentas' => $cuentas,
            'bancos' => $bancos,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $recaudos
     * @param  Collection<string, ComprobanteRecaudoCuenta>  $cuentas
     * @param  Collection<int, Banco>  $bancos
     * @return array{lineas: list<array{cuenta: string, nombre: string, debito: float, credito: float}>, debito: float, credito: float, diferencia: float}
     */
    private function comprobante(Collection $recaudos, Collection $cuentas, Collection $bancos): array
    {
        $cuentaCartera = (string) $cuentas->get(ComprobanteRecaudoCuenta::CARTERA_CLIENTES)?->cuenta;
        $lineas = [];

        $islas = (float) $recaudos->where('concepto', self::CONCEPTO_ISLAS)->sum('valor');
        if ($islas > 0) {
            $lineas[] = [
                'cuenta' => (string) $cuentas->get(ComprobanteRecaudoCuenta::RECAUDO_ISLAS)?->cuenta,
                'nombre' => 'RECAUDO POR ISLAS',
                'debito' => $islas,
                'credito' => 0.0,
            ];
        }

        $recaudos->where('concepto', self::CONCEPTO_ADMIN)
            ->groupBy('banco')
            ->sortKeys()
            ->each(function (Collection $delBanco, string $nombre) use (&$lineas, $bancos): void {
                $lineas[] = [
                    'cuenta' => (string) $bancos->firstWhere('name', $nombre)?->cuenta_contable,
                    'nombre' => $nombre,
                    'debito' => (float) $delBanco->sum('valor'),
                    'credito' => 0.0,
                ];
            });

        // Cada abono va en forma individual, por cliente.
        foreach ($recaudos->sortBy('cliente', SORT_NATURAL | SORT_FLAG_CASE) as $recaudo) {
            $lineas[] = [
                'cuenta' => $cuentaCartera,
                'nombre' => $recaudo['cliente'],
                'debito' => 0.0,
                'credito' => $recaudo['valor'],
            ];
        }

        $debito = (float) array_sum(array_column($lineas, 'debito'));
        $credito = (float) array_sum(array_column($lineas, 'credito'));

        return ['lineas' => $lineas, 'debito' => $debito, 'credito' => $credito, 'diferencia' => $credito - $debito];
    }
}
