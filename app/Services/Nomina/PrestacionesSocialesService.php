<?php

namespace App\Services\Nomina;

use App\Models\Nomina\Empleado;
use App\Models\Nomina\LiquidacionNomina;
use App\Models\Nomina\LiquidacionNominaEmpleado;
use App\Models\Nomina\ParametroLiquidacion;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Acumulados de nómina, prima semestral, consolidación anual de prestaciones y liquidación
 * del contrato (hojas ACUMULADORES, CONSOLIDACION PRESTAC y LIQUIDADOR PREST. SOC.).
 * Solo cuentan las nóminas APROBADAS. Los días se cuentan como DAYS360 de Excel (método
 * europeo) incluyendo el día final.
 */
class PrestacionesSocialesService
{
    /**
     * Días entre dos fechas sobre año de 360 días, inclusive (=DAYS360(desde; hasta; VERDADERO)+1).
     */
    public function dias360(CarbonInterface $desde, CarbonInterface $hasta): int
    {
        if ($hasta->lt($desde)) {
            return 0;
        }

        $diaDesde = min(30, $desde->day);
        $diaHasta = min(30, $hasta->day);

        return ($hasta->year - $desde->year) * 360 + ($hasta->month - $desde->month) * 30 + ($diaHasta - $diaDesde) + 1;
    }

    /**
     * Filas de nóminas aprobadas de un año (o de un rango de meses).
     *
     * @return Collection<int, LiquidacionNominaEmpleado>
     */
    public function filasAprobadas(int $anio, int $mesDesde = 1, int $mesHasta = 12, ?int $empleadoId = null): Collection
    {
        return LiquidacionNominaEmpleado::query()
            ->with(['liquidacion', 'empleado'])
            ->whereHas('liquidacion', fn ($query) => $query->where('estado', LiquidacionNomina::ESTADO_APROBADA)
                ->where('anio', $anio)->whereBetween('mes', [$mesDesde, $mesHasta]))
            ->when($empleadoId, fn ($query, int $id) => $query->where('empleado_id', $id))
            ->get();
    }

    /**
     * Ingreso salarial del mes para promedios de prestaciones: básico, recargos y auxilio de transporte.
     */
    public function ingresoSalarial(LiquidacionNominaEmpleado $fila): float
    {
        return $fila->basico + $fila->recargo_nocturno + $fila->recargo_festivo + $fila->auxilio_transporte;
    }

    /**
     * Acumulado anual por empleado (hoja ACUMULADORES).
     *
     * @return list<array{empleado: Empleado, meses: int, sueldo: float, sobresueldos: float, auxilio: float, total_salarios: float,
     *     cesantias: float, intereses: float, total_cesantias: float, vacaciones: float, prima: float, total_prestaciones: float,
     *     total_ingresos: float, salud: float, pension: float, total_descuentos: float, anticipos_faltantes: float, neto: float}>
     */
    public function acumulados(int $anio): array
    {
        return $this->filasAprobadas($anio)
            ->groupBy('empleado_id')
            ->map(fn (Collection $filas): array => $this->sumarAcumulado($filas))
            ->sortBy(fn (array $fila): string => $fila['empleado']->nombre_completo)
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, LiquidacionNominaEmpleado>  $filas
     * @return array<string, mixed>
     */
    public function sumarAcumulado(Collection $filas): array
    {
        $sueldo = (float) $filas->sum('basico');
        $sobresueldos = (float) $filas->sum(fn (LiquidacionNominaEmpleado $fila): float => $fila->totalRecargos());
        $auxilio = (float) $filas->sum('auxilio_transporte');
        $cesantias = (float) $filas->sum('cesantias');
        $intereses = (float) $filas->sum('intereses_cesantias');
        $vacaciones = (float) $filas->sum('vacaciones');
        $prima = (float) $filas->sum('prima');
        $salud = (float) $filas->sum('salud');
        $pension = (float) $filas->sum('pension');
        $totalSalarios = $sueldo + $sobresueldos + $auxilio;
        $totalPrestaciones = $vacaciones + $prima;

        return [
            'empleado' => $filas->first()->empleado,
            'meses' => $filas->count(),
            'sueldo' => $sueldo,
            'sobresueldos' => $sobresueldos,
            'auxilio' => $auxilio,
            'total_salarios' => $totalSalarios,
            'cesantias' => $cesantias,
            'intereses' => $intereses,
            'total_cesantias' => $cesantias + $intereses,
            'vacaciones' => $vacaciones,
            'prima' => $prima,
            'total_prestaciones' => $totalPrestaciones,
            'total_ingresos' => $totalSalarios + $cesantias + $intereses + $totalPrestaciones,
            'salud' => $salud,
            'pension' => $pension,
            'total_descuentos' => $salud + $pension,
            'anticipos_faltantes' => (float) $filas->sum(fn (LiquidacionNominaEmpleado $fila): float => $fila->anticipos + $fila->faltantes),
            'neto' => (float) $filas->sum('neto_pagar'),
        ];
    }

    /**
     * Ingreso salarial por mes (1..12) de cada empleado en un año.
     *
     * @return array<int, array<int, float>> empleado_id => [mes => valor]
     */
    public function ingresosPorMes(int $anio, int $mesDesde = 1, int $mesHasta = 12): array
    {
        $resultado = [];
        foreach ($this->filasAprobadas($anio, $mesDesde, $mesHasta) as $fila) {
            $resultado[$fila->empleado_id][$fila->liquidacion->mes] = ($resultado[$fila->empleado_id][$fila->liquidacion->mes] ?? 0) + $this->ingresoSalarial($fila);
        }

        return $resultado;
    }

    /**
     * Prima de servicios del semestre (hoja LIQUIDADOR, bloque PRIMA SEMESTRAL):
     * promedio de los ingresos del semestre × días trabajados en el semestre / 360.
     *
     * @return list<array{empleado: Empleado, fecha_ingreso: ?CarbonInterface, dias: int, meses: array<int, float>, total: float,
     *     promedio: float, prima: float, sin_historial: bool, ya_pagada: float}>
     */
    public function primaSemestral(int $anio, int $semestre): array
    {
        $mesDesde = $semestre === 1 ? 1 : 7;
        $mesHasta = $mesDesde + 5;
        $inicio = CarbonImmutable::create($anio, $mesDesde, 1);
        $fin = CarbonImmutable::create($anio, $mesHasta, 1)->endOfMonth()->startOfDay();
        $ingresos = $this->ingresosPorMes($anio, $mesDesde, $mesHasta);
        $pagada = $this->filasAprobadas($anio, $mesDesde, $mesHasta)->groupBy('empleado_id')->map(fn (Collection $filas): float => (float) $filas->sum('prima'));

        return $this->empleadosVinculados($inicio, $fin)
            ->map(function (Empleado $empleado) use ($inicio, $fin, $ingresos, $pagada): array {
                $dias = min(180, $this->dias360($this->desde($empleado, $inicio), $this->hasta($empleado, $fin)));
                $meses = $ingresos[$empleado->id] ?? [];
                $promedio = $this->promedio($meses, $empleado, $fin);

                return [
                    'empleado' => $empleado,
                    'fecha_ingreso' => $empleado->fecha_ingreso,
                    'dias' => $dias,
                    'meses' => $meses,
                    'total' => array_sum($meses),
                    'promedio' => $promedio,
                    'prima' => round($promedio * $dias / 360),
                    'sin_historial' => $meses === [],
                    'ya_pagada' => (float) ($pagada[$empleado->id] ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Consolidación de prestaciones al 31 de diciembre (hoja CONSOLIDACION PRESTAC).
     *
     * @return list<array{empleado: Empleado, dias: int, meses: array<int, float>, total: float, promedio: float,
     *     cesantias: float, intereses: float, vacaciones: float, prima_pagada: float, vacaciones_pagadas: float,
     *     salud: float, pension: float, sin_historial: bool}>
     */
    public function consolidacion(int $anio): array
    {
        $inicio = CarbonImmutable::create($anio, 1, 1);
        $fin = CarbonImmutable::create($anio, 12, 31);
        $ingresos = $this->ingresosPorMes($anio);
        $filas = $this->filasAprobadas($anio)->groupBy('empleado_id');

        return $this->empleadosVinculados($inicio, $fin)
            ->map(function (Empleado $empleado) use ($inicio, $fin, $ingresos, $filas): array {
                $dias = min(360, $this->dias360($this->desde($empleado, $inicio), $this->hasta($empleado, $fin)));
                $meses = $ingresos[$empleado->id] ?? [];
                $promedio = $this->promedio($meses, $empleado, $fin);
                $cesantias = round($promedio * $dias / 360);
                $delEmpleado = $filas->get($empleado->id, collect());

                return [
                    'empleado' => $empleado,
                    'dias' => $dias,
                    'meses' => $meses,
                    'total' => array_sum($meses),
                    'promedio' => $promedio,
                    'cesantias' => $cesantias,
                    'intereses' => round($cesantias * 0.12 * $dias / 360),
                    'vacaciones' => round($promedio * $dias / 720),
                    'prima_pagada' => (float) $delEmpleado->sum('prima'),
                    'vacaciones_pagadas' => (float) $delEmpleado->sum('vacaciones'),
                    'salud' => (float) $delEmpleado->sum('salud'),
                    'pension' => (float) $delEmpleado->sum('pension'),
                    'sin_historial' => $meses === [],
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Liquidación del contrato al retiro (hoja LIQUIDADOR PREST. SOC.).
     *
     * @param  array{sueldo_pendiente?: float, recargos_nocturnos?: float, recargos_festivos?: float, auxilio_transporte?: float, otras_deducciones?: float}  $pendientes
     * @return array<string, mixed>
     */
    public function liquidarContrato(Empleado $empleado, CarbonImmutable $fechaRetiro, array $pendientes = []): array
    {
        $ingreso = CarbonImmutable::parse($empleado->fecha_ingreso);
        $inicioAnio = $fechaRetiro->startOfYear();
        $inicioSemestre = $fechaRetiro->month <= 6 ? $inicioAnio : $inicioAnio->setMonth(7);
        $diasAnio = $this->dias360($ingreso->max($inicioAnio), $fechaRetiro);
        $diasSemestre = $this->dias360($ingreso->max($inicioSemestre), $fechaRetiro);
        $base = $this->salarioBaseLiquidacion($empleado, $fechaRetiro);

        $cesantias = round($base / 360 * $diasAnio);
        $parametros = ParametroLiquidacion::vigenteEn($fechaRetiro);
        $baseDeducciones = ($pendientes['sueldo_pendiente'] ?? 0) + ($pendientes['recargos_nocturnos'] ?? 0) + ($pendientes['recargos_festivos'] ?? 0);
        $salud = round($baseDeducciones * (float) ($parametros?->porcentaje_salud_empleado ?? 4) / 100);
        $pension = round($baseDeducciones * (float) ($parametros?->porcentaje_pension_empleado ?? 4) / 100);

        $datos = [
            'fecha_ingreso' => $ingreso->toDateString(),
            'fecha_retiro' => $fechaRetiro->toDateString(),
            'salario_base' => round($base, 2),
            'dias_anio' => $diasAnio,
            'dias_semestre' => $diasSemestre,
            'dias_contrato' => $this->dias360($ingreso, $fechaRetiro),
            'sueldo_pendiente' => (float) ($pendientes['sueldo_pendiente'] ?? 0),
            'recargos_nocturnos' => (float) ($pendientes['recargos_nocturnos'] ?? 0),
            'recargos_festivos' => (float) ($pendientes['recargos_festivos'] ?? 0),
            'auxilio_transporte' => (float) ($pendientes['auxilio_transporte'] ?? 0),
            'cesantias' => $cesantias,
            'intereses_cesantias' => round($cesantias * 0.12 / 360 * $diasAnio),
            'prima' => round($base / 2 / 180 * $diasSemestre),
            'vacaciones' => round($base / 2 / 360 * $diasAnio),
            'salud' => $salud,
            'pension' => $pension,
            'otras_deducciones' => (float) ($pendientes['otras_deducciones'] ?? 0),
        ];

        $datos['neto_pagar'] = $datos['sueldo_pendiente'] + $datos['recargos_nocturnos'] + $datos['recargos_festivos'] + $datos['auxilio_transporte']
            + $datos['cesantias'] + $datos['intereses_cesantias'] + $datos['prima'] + $datos['vacaciones']
            - $datos['salud'] - $datos['pension'] - $datos['otras_deducciones'];

        return $datos;
    }

    /**
     * Salario base de liquidación: promedio de los últimos 12 meses liquidados (ingreso salarial).
     * Sin historial: sueldo básico más auxilio de transporte vigente.
     */
    public function salarioBaseLiquidacion(Empleado $empleado, CarbonImmutable $hasta): float
    {
        $filas = LiquidacionNominaEmpleado::query()
            ->with('liquidacion')
            ->where('empleado_id', $empleado->id)
            ->whereHas('liquidacion', fn ($query) => $query->where('estado', LiquidacionNomina::ESTADO_APROBADA)
                ->whereRaw('(anio * 12 + mes) <= ?', [$hasta->year * 12 + $hasta->month])
                ->whereRaw('(anio * 12 + mes) > ?', [$hasta->year * 12 + $hasta->month - 12]))
            ->get()
            // Un mes con pocos días (ingreso o retiro) se lleva a 30 días para no bajar el promedio.
            ->filter(fn (LiquidacionNominaEmpleado $fila): bool => $fila->dias_trabajados > 0);

        if ($filas->isEmpty()) {
            return $this->salarioMasAuxilio($empleado, $hasta);
        }

        return (float) $filas->avg(fn (LiquidacionNominaEmpleado $fila): float => $this->ingresoSalarial($fila) / $fila->dias_trabajados * 30);
    }

    /**
     * Control de vacaciones: 15 días hábiles por cada 360 días trabajados, menos los
     * registrados (disfrutados o pagados en dinero). Semáforo: rojo con dos periodos o más
     * acumulados, amarillo con un periodo cumplido pendiente.
     *
     * @return array{dias_laborados: int, periodos: int, causados: float, tomados: int, saldo: float, semaforo: string}
     */
    public function controlVacaciones(Empleado $empleado, ?CarbonInterface $hasta = null): array
    {
        $corte = ! $empleado->activo && $empleado->fecha_retiro ? CarbonImmutable::parse($empleado->fecha_retiro) : CarbonImmutable::parse($hasta ?? now());
        $dias = $empleado->fecha_ingreso ? $this->dias360(CarbonImmutable::parse($empleado->fecha_ingreso), $corte) : 0;
        $causados = round($dias / 360 * 15, 2);
        $registros = $empleado->relationLoaded('vacaciones') ? $empleado->vacaciones : $empleado->vacaciones()->get();
        $tomados = (int) $registros->sum(fn ($registro): int => $registro->dias_disfrutados + $registro->dias_dinero);
        $saldo = round($causados - $tomados, 2);

        return [
            'dias_laborados' => $dias,
            'periodos' => intdiv($dias, 360),
            'causados' => $causados,
            'tomados' => $tomados,
            'saldo' => $saldo,
            'semaforo' => $saldo >= 30 ? 'rojo' : ($saldo >= 15 ? 'amarillo' : 'verde'),
        ];
    }

    /**
     * @param  array<int, float>  $meses
     */
    private function promedio(array $meses, Empleado $empleado, CarbonInterface $fecha): float
    {
        $conValor = array_filter($meses, fn (float $valor): bool => $valor > 0);

        return $conValor === [] ? $this->salarioMasAuxilio($empleado, $fecha) : round(array_sum($conValor) / count($conValor), 2);
    }

    private function salarioMasAuxilio(Empleado $empleado, CarbonInterface $fecha): float
    {
        $auxilio = $empleado->recibe_auxilio_transporte ? (float) (ParametroLiquidacion::vigenteEn($fecha)?->auxilio_transporte ?? 0) : 0.0;

        return (float) $empleado->salario_basico + $auxilio;
    }

    /**
     * @return Collection<int, Empleado>
     */
    private function empleadosVinculados(CarbonInterface $inicio, CarbonInterface $fin): Collection
    {
        return Empleado::query()
            ->vinculadosEntre($inicio->toDateString(), $fin->toDateString())
            ->orderBy('nombres')
            ->get();
    }

    private function desde(Empleado $empleado, CarbonImmutable $inicio): CarbonImmutable
    {
        return $empleado->fecha_ingreso ? CarbonImmutable::parse($empleado->fecha_ingreso)->max($inicio) : $inicio;
    }

    private function hasta(Empleado $empleado, CarbonImmutable $fin): CarbonImmutable
    {
        return ! $empleado->activo && $empleado->fecha_retiro ? CarbonImmutable::parse($empleado->fecha_retiro)->min($fin) : $fin;
    }
}
