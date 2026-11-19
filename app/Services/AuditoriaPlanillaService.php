<?php

namespace App\Services;

use App\Models\Turno;
use App\Models\TurnoAuditoria;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Control de planillas borradas o incompletas. Cada guardado deja el resumen de la planilla
 * (filas y valor de cada sección); si quitó filas o bajó el total de ventas frente al guardado
 * anterior, queda una alerta con el detalle, quién y cuándo. También lista las planillas sin
 * ventas o sin dinero recibido y los números de turno que faltan (planillas eliminadas).
 */
class AuditoriaPlanillaService
{
    /**
     * Secciones de la planilla: relación => [nombre en pantalla, columna de valor].
     *
     * @var array<string, array{0: string, 1: string}>
     */
    public const SECCIONES = [
        'ventas' => ['VENTAS', 'valor'],
        'lubricantes' => ['CANASTILLA', 'total'],
        'consignaciones' => ['CONSIGNACIONES', 'valor'],
        'descuentos' => ['DESCUENTOS', 'valor'],
        'cartera' => ['CARTERA', 'valor'],
        'qrPagos' => ['PAGOS ELECTRÓNICOS', 'valor'],
        'recaudos' => ['RECAUDOS', 'valor'],
        'transferencias' => ['PUNTOS REDIMIDOS', 'puntos_redimidos'],
        'gasolinaEds' => ['GASOLINA EDS', 'valor'],
        'varios' => ['VARIOS', 'valor'],
        'recaudosAdmin' => ['RECAUDOS ADMINISTRACIÓN', 'valor'],
    ];

    /**
     * Filas y valor de cada sección de la planilla, y el total de ventas.
     *
     * @return array<string, array{filas: int, valor: float}|float>
     */
    public function resumen(Turno $turno): array
    {
        $resumen = [];

        foreach (self::SECCIONES as $relacion => [, $columna]) {
            $filas = $turno->{$relacion}()->get();
            $resumen[$relacion] = ['filas' => $filas->count(), 'valor' => round((float) $filas->sum($columna), 2)];
        }

        $resumen['total_ventas'] = round((float) $turno->fresh()->total_ventas, 2);

        return $resumen;
    }

    /**
     * Registra el guardado; $antes es el resumen previo (null si la planilla es nueva).
     *
     * @param  array<string, mixed>|null  $antes
     */
    public function registrarGuardado(Turno $turno, ?array $antes, User $usuario): TurnoAuditoria
    {
        $despues = $this->resumen($turno);

        return TurnoAuditoria::create([
            'turno_id' => $turno->id,
            'numero_turno' => $turno->numero_turno,
            'accion' => $antes === null ? TurnoAuditoria::ACCION_CREADA : TurnoAuditoria::ACCION_GUARDADA,
            'resumen' => $despues,
            'alerta' => $antes === null ? null : $this->alerta($antes, $despues),
            'user_id' => $usuario->id,
        ]);
    }

    public function registrarAnulacion(Turno $turno, User $usuario): TurnoAuditoria
    {
        return TurnoAuditoria::create([
            'turno_id' => $turno->id,
            'numero_turno' => $turno->numero_turno,
            'accion' => TurnoAuditoria::ACCION_ANULADA,
            'resumen' => $this->resumen($turno),
            'user_id' => $usuario->id,
        ]);
    }

    /**
     * La planilla revisada vuelve a PENDIENTE DE REVISIÓN: queda marcada para 2ª revisión.
     */
    public function registrarReapertura(Turno $turno, User $usuario): TurnoAuditoria
    {
        return TurnoAuditoria::create([
            'turno_id' => $turno->id,
            'numero_turno' => $turno->numero_turno,
            'accion' => TurnoAuditoria::ACCION_REABIERTA,
            'resumen' => $this->resumen($turno),
            'user_id' => $usuario->id,
        ]);
    }

    /**
     * Estado de revisión de las planillas del periodo: revisada, 2ª revisión (revisada y
     * devuelta a pendiente para corregirla), pendiente de revisión o anulada.
     *
     * @return array{
     *     conteos: array{revisada: int, segunda_revision: int, pendiente: int, anulada: int},
     *     planillas: Collection<int, array{turno: Turno, estado: string, detalle: string}>
     * }
     */
    public function estadoRevision(string $desde, string $hasta): array
    {
        $turnos = Turno::query()
            ->with('user')
            ->whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hasta)
            ->orderBy('numero_turno')
            ->get();

        $reaperturas = TurnoAuditoria::query()
            ->where('accion', TurnoAuditoria::ACCION_REABIERTA)
            ->whereIn('turno_id', $turnos->pluck('id'))
            ->with('user')
            ->latest('id')
            ->get()
            ->unique('turno_id')
            ->keyBy('turno_id');

        $planillas = $turnos->map(function (Turno $turno) use ($reaperturas): array {
            $reapertura = $reaperturas->get($turno->id);

            return match (true) {
                (bool) $turno->anulada => ['turno' => $turno, 'estado' => 'anulada',
                    'detalle' => trim('Anulada por '.$turno->anulada_por.($turno->anulada_at ? ' el '.$turno->anulada_at->format('d/m/Y') : '').'. '.$turno->motivo_anulacion)],
                (bool) $turno->revisado => ['turno' => $turno, 'estado' => 'revisada',
                    'detalle' => 'Revisada por '.$turno->revisado_por.($turno->revisado_at ? ' el '.$turno->revisado_at->format('d/m/Y') : '')],
                $reapertura !== null => ['turno' => $turno, 'estado' => 'segunda_revision',
                    'detalle' => 'Devuelta a pendiente por '.$reapertura->user?->name.' el '.$reapertura->created_at->format('d/m/Y H:i')],
                default => ['turno' => $turno, 'estado' => 'pendiente', 'detalle' => ''],
            };
        });

        $conteos = ['revisada' => 0, 'segunda_revision' => 0, 'pendiente' => 0, 'anulada' => 0];
        foreach ($planillas as $planilla) {
            $conteos[$planilla['estado']]++;
        }

        return ['conteos' => $conteos, 'planillas' => $planillas];
    }

    /**
     * Qué se quitó frente al guardado anterior: secciones con menos filas y total de ventas menor.
     *
     * @param  array<string, mixed>  $antes
     * @param  array<string, mixed>  $despues
     */
    public function alerta(array $antes, array $despues): ?string
    {
        $pesos = fn (float $valor): string => '$'.number_format($valor, 0, ',', '.');
        $cambios = [];

        foreach (self::SECCIONES as $relacion => [$nombre]) {
            $previo = $antes[$relacion]['filas'] ?? 0;
            $actual = $despues[$relacion]['filas'] ?? 0;

            if ($actual < $previo) {
                $cambios[] = $nombre.': '.$previo.' → '.$actual.' filas ('.$pesos($antes[$relacion]['valor'] ?? 0).' → '.$pesos($despues[$relacion]['valor'] ?? 0).')';
            }
        }

        $ventasAntes = (float) ($antes['total_ventas'] ?? 0);
        $ventasDespues = (float) ($despues['total_ventas'] ?? 0);

        if (round($ventasDespues) < round($ventasAntes)) {
            $cambios[] = 'TOTAL VENTAS: '.$pesos($ventasAntes).' → '.$pesos($ventasDespues);
        }

        return $cambios ? 'Se quitaron datos de la planilla. '.implode('; ', $cambios).'.' : null;
    }

    /**
     * Planillas no anuladas del periodo a las que les falta algo: guardadas sin diligenciar
     * (ni ventas ni dinero recibido: se anulan), sin ventas del turno o sin medios de pago.
     *
     * @return Collection<int, array{turno: Turno, motivo: string, sin_diligenciar: bool}>
     */
    public function incompletas(?string $desde = null, ?string $hasta = null): Collection
    {
        $cuadre = app(CuadreTurnoService::class);

        return Turno::query()
            ->noAnulados()
            ->when($desde, fn ($consulta) => $consulta->whereDate('fecha', '>=', $desde))
            ->when($hasta, fn ($consulta) => $consulta->whereDate('fecha', '<=', $hasta))
            ->with(['user', ...CuadreTurnoService::RELACIONES_RECIBIDO, ...CuadreTurnoService::RELACIONES_VENTAS])
            ->orderByDesc('numero_turno')
            ->get()
            ->map(function (Turno $turno) use ($cuadre): ?array {
                $sinVentas = round($cuadre->ventasIapropiada($turno)) == 0.0;
                $sinRecibido = round($cuadre->totalRecibido($turno) - (float) $turno->traslado_sobrante - (float) $turno->traslado_faltante) == 0.0;

                $motivo = match (true) {
                    $sinVentas && $sinRecibido => 'Planilla guardada sin diligenciar: anúlela',
                    $sinVentas => 'Falta ventas del turno (combustible ni canastilla)',
                    $sinRecibido => 'Falta medios de pago (consignaciones, pagos electrónicos, cartera…)',
                    default => null,
                };

                return $motivo ? ['turno' => $turno, 'motivo' => $motivo, 'sin_diligenciar' => $sinVentas && $sinRecibido] : null;
            })
            ->filter()
            ->values();
    }

    /**
     * Números de turno que faltan entre el primero y el último (planillas eliminadas).
     *
     * @return list<int>
     */
    public function numerosFaltantes(): array
    {
        $numeros = Turno::query()->pluck('numero_turno')->map(fn ($numero): int => (int) $numero)->all();

        if (! $numeros) {
            return [];
        }

        return array_values(array_diff(range(min($numeros), max($numeros)), $numeros));
    }
}
