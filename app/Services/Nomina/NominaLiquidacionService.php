<?php

namespace App\Services\Nomina;

use App\Models\Nomina\CuentaContableNomina;
use App\Models\Nomina\Empleado;
use App\Models\Nomina\LiquidacionNomina;
use App\Models\Nomina\LiquidacionNominaEmpleado;
use App\Models\Nomina\ParametroLiquidacion;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Nómina del mes según la hoja NOMINA de la plantilla "NOMINA PARA PROGRAMACION":
 * devengados (básico, vacaciones, recargos, auxilio de transporte, cesantías, intereses y
 * prima), deducciones (salud, pensión, anticipos y faltantes), aportes del empleador,
 * provisiones de prestaciones sociales, forma de pago y contabilización.
 */
class NominaLiquidacionService
{
    public function __construct(private PlanillaTurnosService $planillas) {}

    /**
     * Crea la nómina del mes con los empleados vinculados y los recargos de la planilla de turnos.
     */
    public function crear(int $anio, int $mes): LiquidacionNomina
    {
        $finDelMes = CarbonImmutable::create($anio, $mes, 1)->endOfMonth();
        $parametros = ParametroLiquidacion::vigenteEn($finDelMes);

        if ($parametros === null) {
            throw ValidationException::withMessages([
                'mes' => 'No hay parámetros de liquidación vigentes para '.$finDelMes->locale('es')->translatedFormat('F Y').'. Regístrelos primero.',
            ]);
        }

        return DB::transaction(function () use ($anio, $mes, $parametros): LiquidacionNomina {
            $liquidacion = LiquidacionNomina::create([
                'anio' => $anio,
                'mes' => $mes,
                'parametro_liquidacion_id' => $parametros->id,
                'estado' => LiquidacionNomina::ESTADO_BORRADOR,
            ]);

            $this->sincronizarEmpleados($liquidacion);
            $this->traerRecargos($liquidacion);

            return $liquidacion;
        });
    }

    /**
     * Agrega los empleados vinculados en el mes que aún no están en la nómina.
     *
     * @return int Cuántos se agregaron.
     */
    public function sincronizarEmpleados(LiquidacionNomina $liquidacion): int
    {
        $presentes = $liquidacion->empleados()->pluck('empleado_id');
        $nuevos = Empleado::query()
            ->vinculadosEntre($liquidacion->inicioDelMes()->toDateString(), $liquidacion->finDelMes()->toDateString())
            ->whereNotIn('id', $presentes)
            ->get();

        foreach ($nuevos as $empleado) {
            $fila = $liquidacion->empleados()->make([
                'empleado_id' => $empleado->id,
                'salario_basico' => (float) $empleado->salario_basico,
                'recibe_auxilio_transporte' => $empleado->recibe_auxilio_transporte,
                'aplica_recargos' => $empleado->aplica_recargos,
                'dias_trabajados' => $this->diasSugeridos($empleado, $liquidacion),
                'medio_pago' => $this->medioPagoDe($empleado),
            ]);
            $fila->fill($this->calcular($fila, $liquidacion->parametros))->save();
        }

        return $nuevos->count();
    }

    /**
     * Días a liquidar sobre base 30: el mes completo, o desde el ingreso / hasta el retiro.
     */
    public function diasSugeridos(Empleado $empleado, LiquidacionNomina $liquidacion): int
    {
        $inicio = $liquidacion->inicioDelMes();
        $fin = $liquidacion->finDelMes();
        $desde = $empleado->fecha_ingreso && $empleado->fecha_ingreso->gt($inicio) ? CarbonImmutable::parse($empleado->fecha_ingreso) : $inicio;
        $hasta = ! $empleado->activo && $empleado->fecha_retiro && $empleado->fecha_retiro->lt($fin) ? CarbonImmutable::parse($empleado->fecha_retiro) : $fin;

        if ($desde->equalTo($inicio) && $hasta->equalTo($fin)) {
            return 30;
        }

        $diaFinal = $hasta->equalTo($fin) ? 30 : min(30, $hasta->day);

        return max(0, $diaFinal - min(30, $desde->day) + 1);
    }

    /**
     * Toma las horas nocturnas y festivas de la planilla de turnos del mes.
     *
     * @return bool Si existe la planilla del mes.
     */
    public function traerRecargos(LiquidacionNomina $liquidacion): bool
    {
        $planilla = $liquidacion->planillaTurnos();

        if ($planilla === null) {
            return false;
        }

        $resumen = $this->planillas->resumen($planilla);

        foreach ($liquidacion->empleados()->get() as $fila) {
            $horas = $resumen[$fila->empleado_id] ?? null;
            $fila->fill([
                'horas_nocturnas' => $fila->aplica_recargos && $horas ? round($horas['horas_nocturnas'], 2) : 0,
                'horas_festivas' => $fila->aplica_recargos && $horas ? round($horas['horas_festivas'], 2) : 0,
            ]);
            $fila->fill($this->calcular($fila, $liquidacion->parametros))->save();
        }

        return true;
    }

    /**
     * Guarda las novedades digitadas y recalcula cada fila.
     *
     * @param  array<int|string, array<string, mixed>>  $novedades  id de la fila => campos
     */
    public function guardarNovedades(LiquidacionNomina $liquidacion, array $novedades): void
    {
        DB::transaction(function () use ($liquidacion, $novedades): void {
            foreach ($liquidacion->empleados()->get() as $fila) {
                if (isset($novedades[$fila->id])) {
                    $fila->fill(array_intersect_key($novedades[$fila->id], array_flip(LiquidacionNominaEmpleado::NOVEDADES)));
                }
                $fila->fill($this->calcular($fila, $liquidacion->parametros))->save();
            }
        });
    }

    public function recalcular(LiquidacionNomina $liquidacion): void
    {
        $this->guardarNovedades($liquidacion, []);
    }

    /**
     * Fórmulas de la hoja NOMINA y del desprendible. Todo se redondea a pesos.
     *
     * @return array<string, float>
     */
    public function calcular(LiquidacionNominaEmpleado $fila, ParametroLiquidacion $parametros): array
    {
        $pesos = fn (float $valor): float => round($valor, 0);
        $salarioDia = $fila->salario_basico / 30;

        $basico = $pesos($salarioDia * $fila->dias_trabajados);
        $vacaciones = $pesos($salarioDia * ($fila->dias_vacaciones_disfrutadas + $fila->dias_vacaciones_dinero));
        $recargoNocturno = $fila->aplica_recargos ? $pesos($fila->horas_nocturnas * $parametros->valorHoraRecargoNocturno()) : 0.0;
        $recargoFestivo = $fila->aplica_recargos ? $pesos($fila->horas_festivas * $parametros->valorHoraRecargoFestivo()) : 0.0;
        $baseAportes = $basico + $vacaciones + $recargoNocturno + $recargoFestivo;
        $auxilio = $fila->recibe_auxilio_transporte ? $pesos((float) $parametros->auxilio_transporte / 30 * $fila->dias_trabajados) : 0.0;
        $devengado = $baseAportes + $auxilio + $fila->cesantias + $fila->intereses_cesantias + $fila->prima;

        $salud = $pesos($baseAportes * (float) $parametros->porcentaje_salud_empleado / 100);
        $pension = $pesos($baseAportes * (float) $parametros->porcentaje_pension_empleado / 100);
        $deducciones = $salud + $pension + $fila->anticipos + $fila->faltantes;

        $aportes = [
            'aporte_pension' => $pesos($baseAportes * (float) $parametros->porcentaje_pension_empleador / 100),
            'aporte_salud' => $pesos($baseAportes * (float) $parametros->porcentaje_salud_empleador / 100),
            'aporte_riesgos' => $pesos($baseAportes * (float) $parametros->porcentaje_riesgos_laborales / 100),
            'aporte_caja' => $pesos($baseAportes * (float) $parametros->porcentaje_caja_compensacion / 100),
        ];
        $provisionCesantias = $pesos($devengado * (float) $parametros->porcentaje_provision_cesantias / 100);
        $provisiones = [
            'provision_cesantias' => $provisionCesantias,
            // La plantilla calcula los intereses sobre la provisión de cesantías.
            'provision_intereses' => $pesos($provisionCesantias * (float) $parametros->porcentaje_provision_intereses_cesantias / 100),
            'provision_vacaciones' => $pesos($devengado * (float) $parametros->porcentaje_provision_vacaciones / 100),
            'provision_prima' => $pesos($devengado * (float) $parametros->porcentaje_provision_prima / 100),
        ];

        return [
            'basico' => $basico,
            'vacaciones' => $vacaciones,
            'recargo_nocturno' => $recargoNocturno,
            'recargo_festivo' => $recargoFestivo,
            'base_aportes' => $baseAportes,
            'auxilio_transporte' => $auxilio,
            'total_devengado' => $devengado,
            'salud' => $salud,
            'pension' => $pension,
            'total_deducciones' => $deducciones,
            'neto_pagar' => $devengado - $deducciones,
            ...$aportes,
            ...$provisiones,
            'costo_total' => $devengado + array_sum($aportes) + array_sum($provisiones),
        ];
    }

    /**
     * Suma de cada concepto (columna TOTALES de la hoja NOMINA).
     *
     * @return array<string, float>
     */
    public function totales(LiquidacionNomina $liquidacion): array
    {
        $filas = $liquidacion->relationLoaded('empleados') ? $liquidacion->empleados : $liquidacion->empleados()->get();
        $campos = [...LiquidacionNominaEmpleado::CALCULADOS, 'cesantias', 'intereses_cesantias', 'prima', 'anticipos', 'faltantes', 'horas_nocturnas', 'horas_festivas'];

        return collect($campos)->mapWithKeys(fn (string $campo): array => [$campo => (float) $filas->sum($campo)])->all();
    }

    /**
     * Forma de pago: total por banco / efectivo, que debe ser igual al neto de la nómina.
     *
     * @return array{medios: list<array{medio: string, empleados: int, total: float}>, total: float, neto: float, diferencia: float}
     */
    public function formaPago(LiquidacionNomina $liquidacion): array
    {
        $filas = $liquidacion->relationLoaded('empleados') ? $liquidacion->empleados : $liquidacion->empleados()->get();
        $medios = $filas->groupBy(fn (LiquidacionNominaEmpleado $fila): string => $fila->medio_pago ?: 'SIN MEDIO DE PAGO')
            ->map(fn (Collection $grupo, string $medio): array => ['medio' => $medio, 'empleados' => $grupo->count(), 'total' => (float) $grupo->sum('neto_pagar')])
            ->sortBy('medio')
            ->values()
            ->all();
        $total = array_sum(array_column($medios, 'total'));
        $neto = (float) $filas->sum('neto_pagar');

        return ['medios' => $medios, 'total' => $total, 'neto' => $neto, 'diferencia' => $neto - $total];
    }

    /**
     * Comprobante contable de la nómina (hoja CONTABILIZACION): débitos por los devengados y
     * créditos por salud y pensión, anticipos y faltantes y el neto a pagar (bancos).
     *
     * @return array{lineas: list<array{cuenta: ?string, concepto: string, tercero: string, identificacion: string, debito: float, credito: float}>, debito: float, credito: float}
     */
    public function contabilizacion(LiquidacionNomina $liquidacion): array
    {
        $cuentas = CuentaContableNomina::query()->get()->keyBy('concepto');
        $lineas = [];

        foreach ($liquidacion->empleados()->with('empleado')->get()->sortBy(fn (LiquidacionNominaEmpleado $fila): string => $fila->empleado->nombreParaNomina()) as $fila) {
            $valores = [
                'basico' => $fila->basico,
                'vacaciones' => $fila->vacaciones,
                'recargos' => $fila->totalRecargos(),
                'auxilio_transporte' => $fila->auxilio_transporte,
                'cesantias' => $fila->cesantias,
                'intereses_cesantias' => $fila->intereses_cesantias,
                'prima' => $fila->prima,
                'salud_pension' => $fila->salud + $fila->pension,
                'anticipos_faltantes' => $fila->anticipos + $fila->faltantes,
                'bancos' => $fila->neto_pagar,
            ];

            foreach ($valores as $concepto => $valor) {
                $cuenta = $cuentas->get($concepto);
                if ($cuenta === null || round($valor, 2) == 0.0) {
                    continue;
                }

                $esDebito = $cuenta->naturaleza === CuentaContableNomina::NATURALEZA_DEBITO;
                $lineas[] = [
                    'cuenta' => $cuenta->cuenta,
                    'concepto' => $concepto === 'bancos' ? 'NETO A PAGAR - '.mb_strtoupper((string) $fila->medio_pago) : $cuenta->nombre,
                    'tercero' => $fila->empleado->nombre_completo,
                    'identificacion' => (string) $fila->empleado->numero_documento,
                    'debito' => $esDebito ? $valor : 0.0,
                    'credito' => $esDebito ? 0.0 : $valor,
                ];
            }
        }

        return [
            'lineas' => $lineas,
            'debito' => array_sum(array_column($lineas, 'debito')),
            'credito' => array_sum(array_column($lineas, 'credito')),
        ];
    }

    /**
     * Avisos para revisar antes de aprobar.
     *
     * @return list<string>
     */
    public function alertas(LiquidacionNomina $liquidacion): array
    {
        $alertas = [];
        $planilla = $liquidacion->planillaTurnos();
        $filas = $liquidacion->empleados()->with('empleado')->get();

        if ($filas->contains('aplica_recargos', true)) {
            if ($planilla === null) {
                $alertas[] = 'No existe la planilla de turnos de '.$liquidacion->nombreDelMes().': los recargos van en cero salvo que los digite.';
            } elseif (! $planilla->estaCerrada()) {
                $alertas[] = 'La planilla de turnos de '.$liquidacion->nombreDelMes().' no está cerrada: ciérrela y use «Traer recargos» para tomar las horas definitivas.';
            }
        }

        foreach ($filas as $fila) {
            $nombre = $fila->empleado->nombreParaNomina();
            if ($fila->dias_trabajados + $fila->dias_vacaciones_disfrutadas > 30) {
                $alertas[] = $nombre.': los días trabajados más las vacaciones disfrutadas pasan de 30.';
            }
            if ($fila->neto_pagar < 0) {
                $alertas[] = $nombre.': el neto a pagar es negativo.';
            }
            if (! $fila->medio_pago) {
                $alertas[] = $nombre.': sin medio de pago.';
            }
        }

        return $alertas;
    }

    /**
     * Medios de pago para elegir: Efectivo y los bancos de los empleados.
     *
     * @return list<string>
     */
    public function mediosPago(): array
    {
        return Empleado::query()->whereNotNull('banco')->where('banco', '!=', '')->distinct()->orderBy('banco')->pluck('banco')
            ->push('Efectivo')
            ->unique()
            ->values()
            ->all();
    }

    public function medioPagoDe(Empleado $empleado): string
    {
        return $empleado->forma_pago === 'efectivo' || ! $empleado->banco ? 'Efectivo' : $empleado->banco;
    }
}
