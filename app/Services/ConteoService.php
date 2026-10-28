<?php

namespace App\Services;

use App\Models\Conteo;
use App\Models\ConteoMovimiento;
use App\Models\TurnoConsignacion;
use App\Models\User;
use App\Support\NumberParser;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Cálculos y guardado del CONTEO (plantilla "PROGRAMACION CONTEO"). Los numerales son los de
 * la plantilla:
 *
 * (3) total billetes = suma de las denominaciones y fajos; (2) moneda; subtotal = (3) + (2)
 * (4) retiros antes de este cierre; total cierre = subtotal + (4)
 * (7) total consignaciones internas: del No. siguiente al último del conteo anterior hasta el
 *     último de este conteo, con el valor de la planilla de turno que tiene cada No.
 * (8) diferencia = total cierre - (7)
 * (9) total gastos; (10) a consignar = (3) - (9)
 * (12) total consignaciones en bancos y otras aplicaciones; (13) no consignado = (10) - (12)
 * (15) saldo de moneda que viene del conteo anterior; (16) moneda de este conteo;
 * (17) retiros de moneda; (18) saldo que pasa = (15) + (16) - (17)
 */
class ConteoService
{
    /** Tope de números por conteo, para que un error de digitación no genere miles de filas. */
    private const MAXIMO_CONSIGNACIONES = 500;

    /**
     * @param  array{fecha: string, consignacion_anterior?: int|string|null, saldo_moneda_inicial?: string|null}  $datos
     */
    public function crear(array $datos, User $usuario): Conteo
    {
        return DB::transaction(function () use ($datos, $usuario): Conteo {
            $numero = (int) Conteo::query()->lockForUpdate()->max('numero') + 1;
            $esPrimero = $numero === 1;

            $conteo = Conteo::create([
                'numero' => $numero,
                'fecha' => $datos['fecha'],
                // Solo el primer conteo trae el punto de partida; los demás lo arrastran.
                'consignacion_anterior' => $esPrimero ? (int) ($datos['consignacion_anterior'] ?? 0) : null,
                'saldo_moneda_inicial' => $esPrimero ? NumberParser::money($datos['saldo_moneda_inicial'] ?? null) : null,
                'user_id' => $usuario->id,
            ]);

            foreach (Conteo::DENOMINACIONES as $denominacion) {
                $conteo->billetes()->create(['denominacion' => $denominacion, 'valor' => 0]);
            }

            return $conteo;
        });
    }

    /**
     * @return array<string, float|int|null>
     */
    public function resumen(Conteo $conteo): array
    {
        $billetes = (float) $conteo->billetes()->sum('valor');
        $moneda = (float) $conteo->moneda;
        $retiros = $this->totalMovimientos($conteo, ConteoMovimiento::TIPO_RETIRO);
        $totalCierre = $billetes + $moneda + $retiros;
        $consignaciones = (float) $conteo->consignaciones()->sum('valor');
        $gastos = $this->totalMovimientos($conteo, ConteoMovimiento::TIPO_GASTO);
        $aConsignar = $billetes - $gastos;
        $aplicaciones = (float) $conteo->aplicaciones()->sum('valor');
        $saldoMonedaAnterior = $this->saldoMonedaAnterior($conteo);
        $retirosMoneda = $this->totalMovimientos($conteo, ConteoMovimiento::TIPO_RETIRO_MONEDA);

        return [
            'billetes' => $billetes,
            'moneda' => $moneda,
            'subtotal' => $billetes + $moneda,
            'retiros' => $retiros,
            'total_cierre' => $totalCierre,
            'consignacion_anterior' => $this->consignacionAnterior($conteo),
            'ultima_consignacion' => $conteo->ultima_consignacion,
            'total_consignaciones' => $consignaciones,
            'diferencia' => $totalCierre - $consignaciones,
            'gastos' => $gastos,
            'a_consignar' => $aConsignar,
            'aplicaciones' => $aplicaciones,
            'no_consignado' => $aConsignar - $aplicaciones,
            'saldo_moneda_anterior' => $saldoMonedaAnterior,
            'retiros_moneda' => $retirosMoneda,
            'saldo_moneda_pasa' => $saldoMonedaAnterior + $moneda - $retirosMoneda,
        ];
    }

    /**
     * Último No. de consignación del conteo anterior (en el primero, el que se digitó al crearlo).
     */
    public function consignacionAnterior(Conteo $conteo): int
    {
        if ($conteo->esPrimero()) {
            return (int) $conteo->consignacion_anterior;
        }

        $anterior = $conteo->anterior();

        return $anterior->ultima_consignacion ?? $this->consignacionAnterior($anterior);
    }

    /**
     * Saldo de moneda que pasa del conteo anterior (en el primero, el saldo inicial digitado).
     */
    public function saldoMonedaAnterior(Conteo $conteo): float
    {
        $saldo = 0.0;

        // Se recorre la cadena desde el primer conteo: cada uno pasa su saldo al siguiente.
        foreach (Conteo::query()->where('numero', '<=', $conteo->numero)->orderBy('numero')->get() as $posicion => $paso) {
            $viene = $posicion === 0 ? (float) $paso->saldo_moneda_inicial : $saldo;

            if ($paso->is($conteo)) {
                return $viene;
            }

            $saldo = $viene + (float) $paso->moneda - $this->totalMovimientos($paso, ConteoMovimiento::TIPO_RETIRO_MONEDA);
        }

        return $saldo;
    }

    /**
     * Guarda la tabla (submódulo) indicada del conteo.
     *
     * @param  array<string, mixed>  $datos
     */
    public function guardar(Conteo $conteo, string $seccion, array $datos): void
    {
        if (! $conteo->esUltimo()) {
            throw ValidationException::withMessages(['conteo' => 'Solo se puede modificar el último conteo: los siguientes toman de él el último No. de consignación y el saldo de moneda.']);
        }

        DB::transaction(function () use ($conteo, $seccion, $datos): void {
            match ($seccion) {
                'efectivo' => $this->guardarEfectivo($conteo, $datos),
                'retiros' => $this->guardarMovimientos($conteo, ConteoMovimiento::TIPO_RETIRO, $datos['filas'] ?? []),
                'consignaciones' => $this->guardarConsignaciones($conteo, $datos),
                'gastos' => $this->guardarMovimientos($conteo, ConteoMovimiento::TIPO_GASTO, $datos['filas'] ?? []),
                'aplicaciones' => $this->guardarAplicaciones($conteo, $datos['filas'] ?? []),
                'moneda' => $this->guardarMoneda($conteo, $datos),
            };
        });
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function guardarEfectivo(Conteo $conteo, array $datos): void
    {
        $conteo->billetes()->delete();

        foreach ((array) ($datos['billetes'] ?? []) as $fila) {
            if (! is_array($fila) || trim((string) ($fila['denominacion'] ?? '')) === '') {
                continue;
            }

            $conteo->billetes()->create([
                'denominacion' => $fila['denominacion'],
                'valor' => NumberParser::money($fila['valor'] ?? null),
            ]);
        }

        $conteo->update(['moneda' => NumberParser::money($datos['moneda'] ?? null)]);
    }

    /**
     * @param  array<int, mixed>  $filas
     */
    private function guardarMovimientos(Conteo $conteo, string $tipo, array $filas): void
    {
        $conteo->movimientos()->where('tipo', $tipo)->delete();

        foreach ($filas as $fila) {
            if (! is_array($fila) || $this->filaVacia($fila, ['nombre', 'concepto', 'valor'])) {
                continue;
            }

            $conteo->movimientos()->create([
                'tipo' => $tipo,
                'nombre' => trim((string) ($fila['nombre'] ?? '')) ?: null,
                'concepto' => trim((string) ($fila['concepto'] ?? '')) ?: null,
                'valor' => NumberParser::money($fila['valor'] ?? null),
            ]);
        }
    }

    /**
     * Genera los números del siguiente al último del conteo anterior hasta el último digitado y
     * toma el valor de cada uno de las planillas de turno (consignaciones con ese No.).
     *
     * @param  array<string, mixed>  $datos
     */
    private function guardarConsignaciones(Conteo $conteo, array $datos): void
    {
        // En el primer conteo el último No. del conteo anterior se digita (punto de partida).
        if ($conteo->esPrimero() && array_key_exists('consignacion_anterior', $datos)) {
            $conteo->update(['consignacion_anterior' => (int) $datos['consignacion_anterior']]);
        }

        $desde = $this->consignacionAnterior($conteo) + 1;
        $hasta = (int) ($datos['ultima_consignacion'] ?? 0);

        if ($hasta < $desde) {
            throw ValidationException::withMessages(['ultima_consignacion' => 'El último No. de consignación debe ser mayor que el último del conteo anterior ('.($desde - 1).').']);
        }

        if ($hasta - $desde + 1 > self::MAXIMO_CONSIGNACIONES) {
            throw ValidationException::withMessages(['ultima_consignacion' => 'Son más de '.self::MAXIMO_CONSIGNACIONES.' consignaciones: revise el último No. digitado.']);
        }

        $numeros = range($desde, $hasta);
        $deTurnos = TurnoConsignacion::query()
            ->whereIn('consignacion_no', array_map('strval', $numeros))
            ->get()
            ->groupBy(fn (TurnoConsignacion $fila): int => (int) $fila->consignacion_no);

        $conteo->consignaciones()->delete();

        foreach ($numeros as $numero) {
            $filas = $deTurnos->get($numero);

            $conteo->consignaciones()->create([
                'numero' => $numero,
                'valor' => $filas ? (float) $filas->sum('valor') : 0,
                'turno_id' => $filas?->first()->turno_id,
            ]);
        }

        $conteo->update(['ultima_consignacion' => $hasta]);
    }

    /**
     * @param  array<int, mixed>  $filas
     */
    private function guardarAplicaciones(Conteo $conteo, array $filas): void
    {
        $conteo->aplicaciones()->delete();

        foreach ($filas as $fila) {
            if (! is_array($fila) || $this->filaVacia($fila, ['conteo_destino_id', 'conteo_tercero_id', 'valor'])) {
                continue;
            }

            $conteo->aplicaciones()->create([
                'conteo_destino_id' => $fila['conteo_destino_id'] ?: null,
                'conteo_tercero_id' => $fila['conteo_tercero_id'] ?: null,
                'valor' => NumberParser::money($fila['valor'] ?? null),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function guardarMoneda(Conteo $conteo, array $datos): void
    {
        $this->guardarMovimientos($conteo, ConteoMovimiento::TIPO_RETIRO_MONEDA, $datos['filas'] ?? []);

        // En el primer conteo el saldo que viene se digita (como el saldo inicial de cartera).
        if ($conteo->esPrimero() && array_key_exists('saldo_moneda_inicial', $datos)) {
            $conteo->update(['saldo_moneda_inicial' => NumberParser::money($datos['saldo_moneda_inicial'])]);
        }
    }

    private function totalMovimientos(Conteo $conteo, string $tipo): float
    {
        return (float) $conteo->movimientos()->where('tipo', $tipo)->sum('valor');
    }

    /**
     * @param  array<string, mixed>  $fila
     * @param  list<string>  $campos
     */
    private function filaVacia(array $fila, array $campos): bool
    {
        foreach ($campos as $campo) {
            if (trim((string) ($fila[$campo] ?? '')) !== '') {
                return false;
            }
        }

        return true;
    }
}
