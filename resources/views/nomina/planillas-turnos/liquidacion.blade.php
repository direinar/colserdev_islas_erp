@extends('layouts.app')

@section('title', 'Recargos '.$planilla->nombreDelMes())

@section('content')
    @php($dinero = fn (float $valor): string => '$ '.number_format($valor, 0, ',', '.'))
    @php($horas = fn (float $valor): string => $valor > 0 ? rtrim(rtrim(number_format($valor, 2, ',', '.'), '0'), ',') : '')

    @include('nomina.planillas-turnos.estilos')

    <div class="container-fluid">

        <x-page-header :title="'Liquidación de recargos · '.$planilla->nombreDelMes()" icon="calculator"
            subtitle="RN = recargo nocturno · RF = recargo dominical y festivo. Horas trabajadas por semana (de domingo a sábado) frente a la jornada.">
            <button type="button" class="btn btn-light" onclick="window.print()">Imprimir</button>
            @if ($planilla->estaCerrada())
                <form method="POST" action="{{ route('nomina.planillas-turnos.reabrir', $planilla) }}" class="d-inline"
                    onsubmit="return confirm('¿Reabrir la planilla para modificar los turnos?');">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-light">Reabrir</button>
                </form>
            @else
                <a href="{{ route('nomina.planillas-turnos.edit', $planilla) }}" class="btn btn-light">Programar</a>
                <form method="POST" action="{{ route('nomina.planillas-turnos.cerrar', $planilla) }}" class="d-inline"
                    onsubmit="return confirm('¿Cerrar la planilla? Las horas se recalculan y ya no se podrá editar.');">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-success">Cerrar planilla</button>
                </form>
            @endif
            <a href="{{ route('nomina.planillas-turnos.index') }}" class="btn btn-light">Volver</a>
        </x-page-header>

        @if (session('success'))
            <div class="alert alert-success d-print-none">{{ session('success') }}</div>
        @endif

        @include('nomina.partials.errores-formulario')

        <p class="mb-2">
            Estado:
            @if ($planilla->estaCerrada())
                <span class="badge text-bg-success">Cerrada</span>
            @else
                <span class="badge text-bg-warning">Borrador</span>
            @endif
        </p>

        <h2 class="h5 mt-3">Resumen del mes</h2>
        <div class="table-responsive">
            <table class="table table-bordered table-sm planilla-semana mb-4">
                <thead class="table-primary">
                    <tr>
                        <th class="col-empleado">Empleado</th>
                        <th>Horas trabajadas</th>
                        <th>Horas RN</th>
                        <th>Horas RF</th>
                        <th>Valor RN</th>
                        <th>Valor RF</th>
                        <th>Total recargos</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($empleados as $empleado)
                        @php($fila = $resumen[$empleado->id])
                        <tr>
                            <td class="col-empleado">{{ $empleado->nombre_corto }}</td>
                            <td>{{ $horas($fila['horas_trabajadas']) ?: 0 }}</td>
                            <td>{{ $horas($fila['horas_nocturnas']) ?: 0 }}</td>
                            <td>{{ $horas($fila['horas_festivas']) ?: 0 }}</td>
                            <td class="text-end">{{ $dinero($fila['valor_nocturnas']) }}</td>
                            <td class="text-end">{{ $dinero($fila['valor_festivas']) }}</td>
                            <td class="text-end fw-semibold">{{ $dinero($fila['valor_nocturnas'] + $fila['valor_festivas']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light fw-semibold">
                    <tr>
                        <td class="col-empleado">Totales</td>
                        <td>{{ $horas(collect($resumen)->sum('horas_trabajadas')) ?: 0 }}</td>
                        <td>{{ $horas(collect($resumen)->sum('horas_nocturnas')) ?: 0 }}</td>
                        <td>{{ $horas(collect($resumen)->sum('horas_festivas')) ?: 0 }}</td>
                        <td class="text-end">{{ $dinero(collect($resumen)->sum('valor_nocturnas')) }}</td>
                        <td class="text-end">{{ $dinero(collect($resumen)->sum('valor_festivas')) }}</td>
                        <td class="text-end">{{ $dinero(collect($resumen)->sum('valor_nocturnas') + collect($resumen)->sum('valor_festivas')) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <p class="small text-muted">
            Valores calculados con la hora ordinaria de la tabla de parámetros vigente en cada día, como en la hoja de nómina.
        </p>

        <h2 class="h5 mt-3">Horas trabajadas por semana</h2>
        <div class="table-responsive">
            <table class="table table-bordered table-sm planilla-semana mb-4">
                <thead class="table-primary">
                    <tr>
                        <th class="col-empleado">Empleado</th>
                        @foreach ($semanas as $indice => $semana)
                            <th>
                                Sem. {{ $indice + 1 }}<br>
                                <span class="fw-normal">{{ $semana[0]->format('d/m') }} - {{ $semana[6]->format('d/m') }}</span>
                            </th>
                        @endforeach
                    </tr>
                    <tr class="table-light">
                        <th class="col-empleado fw-normal">Jornada</th>
                        @foreach ($jornadas as $jornada)
                            <th class="fw-normal">{{ $jornada ?? '—' }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($empleados as $empleado)
                        <tr>
                            <td class="col-empleado">{{ $empleado->nombre_corto }}</td>
                            @foreach ($resumen[$empleado->id]['semanas'] as $indice => $horasSemana)
                                <td class="{{ $jornadas[$indice] && $horasSemana > $jornadas[$indice] ? 'exceso' : '' }}">
                                    {{ $horas($horasSemana) ?: 0 }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="small text-muted">
            En rojo, las semanas que superan la jornada. La primera y la última semana pueden incluir días de otro mes que aquí no se suman.
        </p>

        <h2 class="h5 mt-3">Detalle diario</h2>
        @foreach ($semanas as $indice => $semana)
            <div class="table-responsive">
                <table class="table table-bordered table-sm planilla-semana mb-3">
                    <thead class="table-light">
                        <tr>
                            <th class="col-empleado" rowspan="2">Semana {{ $indice + 1 }}</th>
                            @foreach ($semana as $dia)
                                <th colspan="3" class="{{ $dia->month !== $planilla->mes ? 'dia-fuera' : ($dia->isSunday() || in_array($dia->toDateString(), $festivos, true) ? 'dia-festivo' : '') }}">
                                    {{ mb_strtoupper($dia->locale('es')->dayName) }} {{ $dia->day }}
                                </th>
                            @endforeach
                            <th rowspan="2">H. trab.</th>
                        </tr>
                        <tr>
                            @foreach ($semana as $dia)
                                <th>Turno</th>
                                <th>RN</th>
                                <th>RF</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($empleados as $empleado)
                            <tr>
                                <td class="col-empleado">{{ $empleado->nombre_corto }}</td>
                                @foreach ($semana as $dia)
                                    @if ($dia->month !== $planilla->mes)
                                        <td class="dia-fuera" colspan="3"></td>
                                    @elseif ($asignacion = $resumen[$empleado->id]['dias'][$dia->toDateString()] ?? null)
                                        <td style="background-color: {{ $asignacion->turno->color }}">{{ $asignacion->turno->nombre }}</td>
                                        <td>{{ $horas($asignacion->horas_nocturnas) }}</td>
                                        <td>{{ $horas($asignacion->horas_festivas) }}</td>
                                    @else
                                        <td class="text-muted">—</td>
                                        <td></td>
                                        <td></td>
                                    @endif
                                @endforeach
                                <td class="{{ $jornadas[$indice] && $resumen[$empleado->id]['semanas'][$indice] > $jornadas[$indice] ? 'exceso' : '' }}">
                                    {{ $horas($resumen[$empleado->id]['semanas'][$indice]) ?: 0 }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach

    </div>
@endsection
