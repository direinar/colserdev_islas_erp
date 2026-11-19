<?php

namespace App\Services;

use App\Models\Conteo;
use App\Models\ConteoConsignacion;
use App\Models\ConteoMovimiento;
use App\Models\TurnoConsignacion;
use App\Models\User;
use App\Support\NumberParser;
use Illuminate\Support\Collection;
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
 * (12) total consignaciones en bancos y otras aplicaciones;
 * (13) no consignado = (10) + diferencias no consignadas de conteos anteriores - (12)
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
        $noConsignadoAnterior = $this->noConsignadoAnterior($conteo);
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
            'no_consignado_anterior' => $noConsignadoAnterior,
            'no_consignado' => $aConsignar + $noConsignadoAnterior - $aplicaciones,
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
     * Consignaciones de planillas de turno que se salieron del consecutivo: su No. ya debió
     * contarse (es menor o igual al último No. de este conteo) pero no está en ningún conteo,
     * por ejemplo un número del hueco de un cambio de talonario o uno ya superado. Los números
     * anteriores al punto de partida del primer conteo no se revisan.
     *
     * @return Collection<int, TurnoConsignacion>
     */
    public function fueraDeConsecutivo(Conteo $conteo): Collection
    {
        if (! $conteo->ultima_consignacion) {
            return collect();
        }

        $inicio = (int) Conteo::query()->orderBy('numero')->value('consignacion_anterior') + 1;
        $contados = ConteoConsignacion::query()
            ->whereNotNull('numero')
            ->whereHas('conteo', fn ($query) => $query->where('numero', '<=', $conteo->numero))
            ->pluck('numero')
            ->flip();

        return TurnoConsignacion::query()
            ->whereHas('turno', fn ($turno) => $turno->noAnulados())
            ->with('turno')
            ->get()
            ->filter(function (TurnoConsignacion $fila) use ($inicio, $conteo, $contados): bool {
                $numero = trim((string) $fila->consignacion_no);

                return ctype_digit($numero)
                    && (int) $numero >= $inicio
                    && (int) $numero <= $conteo->ultima_consignacion
                    && ! $contados->has((int) $numero);
            })
            ->sortBy(fn (TurnoConsignacion $fila): int => (int) $fila->consignacion_no)
            ->values();
    }

    /**
     * Diferencia no consignada que viene del conteo anterior: lo que quedó por consignar allí,
     * que ya incluye lo que a su vez venía de los anteriores. Se consigna en este conteo.
     * (13) no consignado = (10) a consignar + no consignado anterior - (12) consignaciones y otros.
     */
    public function noConsignadoAnterior(Conteo $conteo): float
    {
        $pendiente = 0.0;

        foreach (Conteo::query()->where('numero', '<', $conteo->numero)->orderBy('numero')->get() as $paso) {
            $aConsignar = (float) $paso->billetes()->sum('valor') - $this->totalMovimientos($paso, ConteoMovimiento::TIPO_GASTO);
            // Solo pasa lo que quedó sin consignar; un exceso consignado (negativo) no pasa.
            $pendiente = max(0.0, $aConsignar + $pendiente - (float) $paso->aplicaciones()->sum('valor'));
        }

        return $pendiente;
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
        [$talonarioFin, $talonarioInicio] = $this->cambioTalonario($datos, $desde, $hasta);

        if ($hasta < $desde) {
            throw ValidationException::withMessages(['ultima_consignacion' => 'El último No. de consignación debe ser mayor que el último del conteo anterior ('.($desde - 1).').']);
        }

        // Con cambio de talonario los números entre el fin del anterior y el inicio del nuevo no existen.
        $numeros = $talonarioFin === null
            ? range($desde, $hasta)
            : [...($talonarioFin >= $desde ? range($desde, $talonarioFin) : []), ...range($talonarioInicio, $hasta)];

        if (count($numeros) > self::MAXIMO_CONSIGNACIONES) {
            throw ValidationException::withMessages(['ultima_consignacion' => 'Son más de '.self::MAXIMO_CONSIGNACIONES.' consignaciones: revise el último No. digitado o el cambio de talonario.']);
        }
        $deTurnos = TurnoConsignacion::query()
            ->whereIn('consignacion_no', array_map('strval', $numeros))
            // Una planilla anulada no aporta consignaciones al conteo.
            ->whereHas('turno', fn ($turno) => $turno->noAnulados())
            ->get()
            ->groupBy(fn (TurnoConsignacion $fila): int => (int) $fila->consignacion_no);

        // Se regenera todo: el consecutivo con los valores de las planillas y las filas manuales
        // tal como vienen del formulario.
        $conteo->consignaciones()->delete();

        foreach ($numeros as $numero) {
            $filas = $deTurnos->get($numero);

            $conteo->consignaciones()->create([
                'numero' => $numero,
                'valor' => $filas ? (float) $filas->sum('valor') : 0,
                'turno_id' => $filas?->first()->turno_id,
            ]);
        }

        foreach ($this->filasManuales($datos['manuales'] ?? []) as $fila) {
            $conteo->consignaciones()->create($fila + ['manual' => true]);
        }

        $conteo->update([
            'ultima_consignacion' => $hasta,
            'talonario_fin' => $talonarioFin,
            'talonario_inicio' => $talonarioInicio,
        ]);
    }

    /**
     * Cambio de talonario dentro del conteo: el consecutivo llega hasta el último No. del
     * talonario anterior y sigue desde el primero del nuevo. Sin datos, no hay cambio.
     *
     * @param  array<string, mixed>  $datos
     * @return array{0: int|null, 1: int|null}
     */
    private function cambioTalonario(array $datos, int $desde, int $hasta): array
    {
        $fin = trim((string) ($datos['talonario_fin'] ?? ''));
        $inicio = trim((string) ($datos['talonario_inicio'] ?? ''));

        if ($fin === '' && $inicio === '') {
            return [null, null];
        }

        if ($fin === '' || $inicio === '') {
            throw ValidationException::withMessages(['talonario_inicio' => 'Para el cambio de talonario digite el último No. del talonario anterior y el primer No. del nuevo.']);
        }

        [$fin, $inicio] = [(int) $fin, (int) $inicio];

        if ($fin < $desde - 1) {
            throw ValidationException::withMessages(['talonario_fin' => 'El último No. del talonario anterior no puede ser menor que el último del conteo anterior ('.($desde - 1).').']);
        }

        if ($inicio <= $fin) {
            throw ValidationException::withMessages(['talonario_inicio' => 'El primer No. del talonario nuevo debe ser mayor que el último del talonario anterior ('.$fin.').']);
        }

        if ($inicio > $hasta) {
            throw ValidationException::withMessages(['talonario_inicio' => 'El primer No. del talonario nuevo no puede ser mayor que el último No. de este conteo ('.$hasta.').']);
        }

        return [$fin, $inicio];
    }

    /**
     * Filas manuales de consignaciones: No. opcional, valor y concepto (obligatorio si la fila
     * tiene datos). Las filas en blanco se ignoran.
     *
     * @param  array<int, mixed>  $filas
     * @return list<array{numero: int|null, valor: float, concepto: string}>
     */
    private function filasManuales(array $filas): array
    {
        $manuales = [];

        foreach (array_values($filas) as $posicion => $fila) {
            if (! is_array($fila) || $this->filaVacia($fila, ['numero', 'valor', 'concepto'])) {
                continue;
            }

            $concepto = trim((string) ($fila['concepto'] ?? ''));
            if ($concepto === '') {
                throw ValidationException::withMessages(['manuales.'.$posicion.'.concepto' => 'Escriba el concepto de la fila manual '.($posicion + 1).' de consignaciones.']);
            }

            $numero = trim((string) ($fila['numero'] ?? ''));

            $manuales[] = [
                'numero' => $numero === '' ? null : (int) $numero,
                'valor' => NumberParser::money($fila['valor'] ?? null),
                'concepto' => $concepto,
            ];
        }

        return $manuales;
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
