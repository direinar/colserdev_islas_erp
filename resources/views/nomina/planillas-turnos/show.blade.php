@extends('layouts.app')

@section('title', 'Planilla de turnos '.$planilla->nombreDelMes())

@section('content')
    @include('nomina.planillas-turnos.estilos')

    <div class="container-fluid">

        <x-page-header :title="'Planilla de turnos · '.$planilla->nombreDelMes()" icon="calendar-week"
            subtitle="Horarios de trabajo del mes, sin liquidación de recargos.">
            <button type="button" class="btn btn-light" onclick="window.print()">Imprimir</button>
            @unless ($planilla->estaCerrada())
                <a href="{{ route('nomina.planillas-turnos.edit', $planilla) }}" class="btn btn-light">Programar</a>
            @endunless
            <a href="{{ route('nomina.planillas-turnos.liquidacion', $planilla) }}" class="btn btn-light">Recargos</a>
            <a href="{{ route('nomina.planillas-turnos.index') }}" class="btn btn-light">Volver</a>
        </x-page-header>

        @if (session('error'))
            <div class="alert alert-danger d-print-none">{{ session('error') }}</div>
        @endif

        @foreach ($semanas as $indice => $semana)
            <div class="table-responsive">
                <table class="table table-bordered table-sm planilla-semana mb-3">
                    <thead class="table-light">
                        <tr>
                            <th class="col-empleado">Semana {{ $indice + 1 }}</th>
                            @foreach ($semana as $dia)
                                <th class="{{ $dia->month !== $planilla->mes ? 'dia-fuera' : ($dia->isSunday() || in_array($dia->toDateString(), $festivos, true) ? 'dia-festivo' : '') }}">
                                    {{ mb_strtoupper($dia->locale('es')->dayName) }} {{ $dia->day }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($empleados as $empleado)
                            <tr>
                                <td class="col-empleado">{{ $empleado->nombre_corto }}</td>
                                @foreach ($semana as $dia)
                                    @if ($dia->month !== $planilla->mes)
                                        <td class="dia-fuera"></td>
                                    @elseif ($asignacion = $resumen[$empleado->id]['dias'][$dia->toDateString()] ?? null)
                                        <td style="background-color: {{ $asignacion->turno->color }}">{{ $asignacion->turno->nombre }}</td>
                                    @else
                                        <td class="text-muted">Descanso</td>
                                    @endif
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach

        @if ($planilla->observaciones)
            <p><strong>Observaciones:</strong> {{ $planilla->observaciones }}</p>
        @endif

    </div>
@endsection
