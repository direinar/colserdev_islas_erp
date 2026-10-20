<?php

namespace App\Services;

use App\Models\Turno;
use App\Models\TurnoLubricante;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Indicadores y series del dashboard, todos tomados de la planilla de turnos: ventas según
 * cierres de IAPROPIADA (tirillas) más canastilla, galones por combustible, formas de pago
 * y estado de revisión. El islero solo ve las planillas que él registró.
 */
class DashboardService
{
    /**
     * Rangos más largos se agrupan por mes para que la gráfica siga siendo legible.
     */
    public const MAXIMO_DIAS_POR_DIA = 62;

    /**
     * Formas de pago del "total recibido" de la planilla (columna => etiqueta).
     */
    public const FORMAS_PAGO = [
        'consignaciones' => 'Consignaciones',
        'pagos_electronicos' => 'Pagos electrónicos',
        'cartera' => 'Crédito directo',
        'puntos' => 'Puntos redimidos',
        'gasolina_eds' => 'Gasolina EDS',
        'descuentos' => 'Descuentos',
        'varios' => 'Varios',
    ];

    /**
     * @return array{
     *     kpis: array{ventas: float, combustible: float, canastilla: float, galones_corriente: float, galones_acpm: float, turnos: int, pendientes_revision: int, descuadrados: int},
     *     agrupacion: string,
     *     periodos: list<array{etiqueta: string, gasolina: float, acpm: float, canastilla: float, galones_corriente: float, galones_acpm: float}>,
     *     formas_pago: list<array{etiqueta: string, valor: float}>,
     *     productos: list<array{producto: string, cantidad: float, total: float}>,
     *     ultimos: Collection<int, Turno>
     * }
     */
    public function resumen(string $desde, string $hasta, User $usuario): array
    {
        $turnos = $this->planillas($usuario)
            ->whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hasta)
            ->withSum('lubricantes as canastilla_total', 'total')
            ->withSum('consignaciones as consignaciones_total', 'valor')
            ->withSum('qrPagos as pagos_electronicos_total', 'valor')
            ->withSum('cartera as cartera_total', 'valor')
            ->withSum('transferencias as puntos_total', 'puntos_redimidos')
            ->withSum('gasolinaEds as gasolina_eds_total', 'valor')
            ->withSum('descuentos as descuentos_total', 'valor')
            ->withSum('varios as varios_total', 'valor')
            ->orderBy('fecha')
            ->orderBy('numero_turno')
            ->get();

        $combustible = (float) $turnos->sum(fn (Turno $turno): float => (float) $turno->tirillas_valor_corriente + (float) $turno->tirillas_valor_acpm);
        $canastilla = (float) $turnos->sum('canastilla_total');

        return [
            'kpis' => [
                'ventas' => $combustible + $canastilla,
                'combustible' => $combustible,
                'canastilla' => $canastilla,
                'galones_corriente' => (float) $turnos->sum('tirillas_galones_corriente'),
                'galones_acpm' => (float) $turnos->sum('tirillas_galones_acpm'),
                'turnos' => $turnos->count(),
                'pendientes_revision' => $turnos->where('revisado', false)->count(),
                'descuadrados' => $turnos->filter(fn (Turno $turno): bool => $turno->sobrante_pendiente !== null && abs((float) $turno->sobrante_pendiente) >= 1)->count(),
            ],
            'agrupacion' => $this->agrupacion($desde, $hasta),
            'periodos' => $this->periodos($turnos, $desde, $hasta),
            'formas_pago' => collect(self::FORMAS_PAGO)
                ->map(fn (string $etiqueta, string $columna): array => ['etiqueta' => $etiqueta, 'valor' => (float) $turnos->sum($columna.'_total')])
                ->filter(fn (array $forma): bool => $forma['valor'] != 0)
                ->sortByDesc('valor')
                ->values()
                ->all(),
            'productos' => $this->productosMasVendidos($turnos->pluck('id')),
            'ultimos' => $turnos->sortByDesc('numero_turno')->take(8)->values(),
        ];
    }

    /**
     * @return Builder<Turno>
     */
    private function planillas(User $usuario): Builder
    {
        return Turno::query()
            ->when(! $usuario->isAdministrador() && ! $usuario->isJefePatios(), fn (Builder $consulta) => $consulta->where('user_id', $usuario->id));
    }

    private function agrupacion(string $desde, string $hasta): string
    {
        return Carbon::parse($desde)->diffInDays(Carbon::parse($hasta)) + 1 > self::MAXIMO_DIAS_POR_DIA ? 'mes' : 'dia';
    }

    /**
     * Un punto por día (o por mes) del rango, incluidos los días sin planillas en cero.
     *
     * @param  Collection<int, Turno>  $turnos
     * @return list<array{etiqueta: string, gasolina: float, acpm: float, canastilla: float, galones_corriente: float, galones_acpm: float}>
     */
    private function periodos(Collection $turnos, string $desde, string $hasta): array
    {
        $porMes = $this->agrupacion($desde, $hasta) === 'mes';
        $formato = $porMes ? 'Y-m' : 'Y-m-d';
        $agrupados = $turnos->groupBy(fn (Turno $turno): string => $turno->fecha->format($formato));

        $periodos = [];
        $cursor = Carbon::parse($desde)->startOfDay();
        $fin = Carbon::parse($hasta)->startOfDay();

        if ($porMes) {
            $cursor->startOfMonth();
        }

        while ($cursor->lte($fin)) {
            $grupo = $agrupados->get($cursor->format($formato), collect());

            $periodos[] = [
                'etiqueta' => $porMes ? $cursor->translatedFormat('M Y') : $cursor->format('d/m'),
                'gasolina' => round((float) $grupo->sum('tirillas_valor_corriente')),
                'acpm' => round((float) $grupo->sum('tirillas_valor_acpm')),
                'canastilla' => round((float) $grupo->sum('canastilla_total')),
                'galones_corriente' => round((float) $grupo->sum('tirillas_galones_corriente'), 3),
                'galones_acpm' => round((float) $grupo->sum('tirillas_galones_acpm'), 3),
            ];

            $porMes ? $cursor->addMonth() : $cursor->addDay();
        }

        return $periodos;
    }

    /**
     * Las cinco referencias de canastilla con mayor venta (con IVA) en las planillas del rango.
     *
     * @param  Collection<int, int>  $turnoIds
     * @return list<array{producto: string, cantidad: float, total: float}>
     */
    private function productosMasVendidos(Collection $turnoIds): array
    {
        if ($turnoIds->isEmpty()) {
            return [];
        }

        return TurnoLubricante::query()
            ->whereIn('turno_id', $turnoIds)
            ->whereNotNull('producto')
            ->selectRaw('producto, SUM(cantidad) as cantidad, SUM(total) as total')
            ->groupBy('producto')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn (TurnoLubricante $fila): array => [
                'producto' => (string) $fila->producto,
                'cantidad' => (float) $fila->cantidad,
                'total' => (float) $fila->total,
            ])
            ->all();
    }
}
