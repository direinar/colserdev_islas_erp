<?php

namespace App\Services\Nomina;

use App\Models\Nomina\AsignacionTurno;
use App\Models\Nomina\Empleado;
use App\Models\Nomina\Festivo;
use App\Models\Nomina\ParametroLiquidacion;
use App\Models\Nomina\PlanillaTurno;
use App\Models\Nomina\Turno;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Programación de la planilla de turnos del mes y su liquidación de recargos:
 * semanas de domingo a sábado, empleados que participan, guardado de los turnos
 * con sus horas calculadas y resúmenes por semana y por mes.
 */
class PlanillaTurnosService
{
    public function __construct(private CalculoHorasTurnoService $calculoHoras) {}

    /**
     * Semanas que cubren el mes, cada una de domingo a sábado. Los días de los meses
     * vecinos se incluyen para completar la semana y no se programan.
     *
     * @return list<list<CarbonImmutable>>
     */
    public function semanas(PlanillaTurno $planilla): array
    {
        $dia = $planilla->inicioDelMes()->startOfWeek(CarbonImmutable::SUNDAY);
        $fin = $planilla->finDelMes();
        $semanas = [];

        while ($dia->lte($fin)) {
            $semana = [];
            for ($i = 0; $i < 7; $i++) {
                $semana[] = $dia;
                $dia = $dia->addDay();
            }
            $semanas[] = $semana;
        }

        return $semanas;
    }

    /**
     * Empleados con sobresueldos y recargos que estuvieron vinculados en el mes, más
     * los que ya tienen turnos en la planilla aunque hoy no cumplan esas condiciones.
     *
     * @return Collection<int, Empleado>
     */
    public function empleados(PlanillaTurno $planilla): Collection
    {
        $inicio = $planilla->inicioDelMes()->toDateString();
        $fin = $planilla->finDelMes()->toDateString();

        return Empleado::query()
            ->where(function ($query) use ($inicio, $fin) {
                $query->where('aplica_recargos', true)
                    ->whereDate('fecha_ingreso', '<=', $fin)
                    ->where(fn ($query) => $query->where('activo', true)->orWhereDate('fecha_retiro', '>=', $inicio));
            })
            ->orWhereIn('id', $planilla->asignaciones()->select('empleado_id'))
            ->orderBy('nombre_corto')
            ->get();
    }

    /**
     * Reemplaza los turnos de la planilla por los enviados y calcula sus horas.
     *
     * @param  array<int|string, array<string, int|string|null>>  $turnosPorEmpleado  empleado_id => [Y-m-d => turno_id]
     */
    public function guardar(PlanillaTurno $planilla, array $turnosPorEmpleado): void
    {
        $empleadosValidos = $this->empleados($planilla)->pluck('id')->all();
        $turnos = Turno::all()->keyBy('id');
        $inicio = $planilla->inicioDelMes();
        $fin = $planilla->finDelMes();
        $registros = [];

        foreach ($turnosPorEmpleado as $empleadoId => $dias) {
            if (! in_array((int) $empleadoId, $empleadosValidos, true)) {
                throw ValidationException::withMessages(['turnos' => 'Uno de los empleados no pertenece a esta planilla.']);
            }

            foreach ((array) $dias as $fecha => $turnoId) {
                if ($turnoId === null || $turnoId === '') {
                    continue;
                }

                $dia = CarbonImmutable::createFromFormat('!Y-m-d', (string) $fecha) ?: null;
                if ($dia === null || $dia->lt($inicio) || $dia->gt($fin)) {
                    throw ValidationException::withMessages(['turnos' => "El día {$fecha} no pertenece al mes de la planilla."]);
                }

                if (! $turnos->has((int) $turnoId)) {
                    throw ValidationException::withMessages(['turnos' => 'Uno de los turnos seleccionados no existe.']);
                }

                $registros[] = [
                    'empleado_id' => (int) $empleadoId,
                    'turno_id' => (int) $turnoId,
                    'fecha' => $dia->toDateString(),
                ];
            }
        }

        DB::transaction(function () use ($planilla, $registros) {
            $planilla->asignaciones()->delete();

            foreach ($registros as $registro) {
                $planilla->asignaciones()->create($registro);
            }

            $this->recalcular($planilla);
        });
    }

    /**
     * Vuelve a calcular las horas de todos los turnos con los parámetros y festivos actuales.
     */
    public function recalcular(PlanillaTurno $planilla): void
    {
        $parametros = ParametroLiquidacion::orderByDesc('vigente_desde')->get();
        $festivos = $this->festivosDelPeriodo($planilla);

        $planilla->asignaciones()->with('turno')->get()->each(function (AsignacionTurno $asignacion) use ($parametros, $festivos) {
            $vigencia = $this->vigenciaEn($parametros, $asignacion->fecha);

            if ($vigencia === null) {
                throw ValidationException::withMessages([
                    'turnos' => 'No hay parámetros de liquidación vigentes para el '.$asignacion->fecha->format('d/m/Y').'.',
                ]);
            }

            $asignacion->update($this->calculoHoras->calcular($asignacion->turno, $asignacion->fecha, $vigencia, $festivos));
        });
    }

    /**
     * Resumen por empleado: turnos por día, horas por semana y totales y valores del mes.
     *
     * @return array<int, array{
     *     dias: array<string, AsignacionTurno>,
     *     semanas: list<float>,
     *     horas_trabajadas: float,
     *     horas_nocturnas: float,
     *     horas_festivas: float,
     *     valor_nocturnas: float,
     *     valor_festivas: float
     * }>
     */
    public function resumen(PlanillaTurno $planilla): array
    {
        $semanas = $this->semanas($planilla);
        $parametros = ParametroLiquidacion::orderByDesc('vigente_desde')->get();
        $asignaciones = $planilla->asignaciones()->with('turno')->get()->groupBy('empleado_id');
        $resumen = [];

        foreach ($this->empleados($planilla) as $empleado) {
            $dias = [];
            $fila = [
                'dias' => [],
                'semanas' => array_fill(0, count($semanas), 0.0),
                'horas_trabajadas' => 0.0,
                'horas_nocturnas' => 0.0,
                'horas_festivas' => 0.0,
                'valor_nocturnas' => 0.0,
                'valor_festivas' => 0.0,
            ];

            foreach ($asignaciones->get($empleado->id, collect()) as $asignacion) {
                $dias[$asignacion->fecha->toDateString()] = $asignacion;
                $vigencia = $this->vigenciaEn($parametros, $asignacion->fecha);

                $fila['horas_trabajadas'] += $asignacion->horas_trabajadas;
                $fila['horas_nocturnas'] += $asignacion->horas_nocturnas;
                $fila['horas_festivas'] += $asignacion->horas_festivas;
                $fila['valor_nocturnas'] += $vigencia ? $asignacion->horas_nocturnas * $vigencia->valorHoraRecargoNocturno() : 0;
                $fila['valor_festivas'] += $vigencia ? $asignacion->horas_festivas * $vigencia->valorHoraRecargoFestivo() : 0;
            }

            foreach ($semanas as $indice => $semana) {
                foreach ($semana as $dia) {
                    $fila['semanas'][$indice] += isset($dias[$dia->toDateString()]) ? $dias[$dia->toDateString()]->horas_trabajadas : 0;
                }
            }

            $fila['dias'] = $dias;
            $resumen[$empleado->id] = $fila;
        }

        return $resumen;
    }

    /**
     * Jornada semanal máxima vigente al iniciar cada semana (para resaltar los excesos).
     *
     * @return list<int|null>
     */
    public function jornadaPorSemana(PlanillaTurno $planilla): array
    {
        $parametros = ParametroLiquidacion::orderByDesc('vigente_desde')->get();

        return array_map(
            fn (array $semana) => $this->vigenciaEn($parametros, $semana[0])?->horas_semanales,
            $this->semanas($planilla),
        );
    }

    /**
     * Festivos del mes y del primer día del mes siguiente (por los turnos que pasan la medianoche).
     *
     * @return list<string>
     */
    public function festivosDelPeriodo(PlanillaTurno $planilla): array
    {
        return Festivo::query()
            ->whereDate('fecha', '>=', $planilla->inicioDelMes()->toDateString())
            ->whereDate('fecha', '<=', $planilla->finDelMes()->addDay()->toDateString())
            ->get()
            ->map(fn (Festivo $festivo) => $festivo->fecha->toDateString())
            ->all();
    }

    /**
     * @param  Collection<int, ParametroLiquidacion>  $parametros  Ordenados de la más reciente a la más antigua.
     */
    private function vigenciaEn(Collection $parametros, CarbonImmutable|\DateTimeInterface $fecha): ?ParametroLiquidacion
    {
        $dia = CarbonImmutable::parse($fecha)->toDateString();

        return $parametros->first(fn (ParametroLiquidacion $parametro) => $parametro->vigente_desde->toDateString() <= $dia);
    }
}
