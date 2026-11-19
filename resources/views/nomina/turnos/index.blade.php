@extends('layouts.app')

@section('title', 'Turnos')

@section('content')
    <div class="container">

        <x-page-header title="Turnos" icon="clock"
            subtitle="Catálogo de turnos para la planilla. Las horas nocturnas y festivas se calculan solas al asignarlos.">
            <a href="{{ route('nomina.turnos.create') }}" class="btn btn-light">Nuevo turno</a>
        </x-page-header>

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($parametros)
            <p class="text-muted small">
                Recargo nocturno de {{ substr($parametros->hora_inicio_nocturna, 0, 5) }} a {{ substr($parametros->hora_fin_nocturna, 0, 5) }};
                recargo dominical/festivo de {{ substr($parametros->hora_inicio_festiva, 0, 5) }} a {{ substr($parametros->hora_fin_festiva, 0, 5) }}
                (parámetros vigentes desde {{ $parametros->vigente_desde->format('d/m/Y') }}).
            </p>
        @else
            <div class="alert alert-warning">Sin parámetros de liquidación vigentes no se pueden calcular las horas nocturnas.</div>
        @endif

        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-primary">
                    <tr>
                        <th>Turno</th>
                        <th>Horario</th>
                        <th class="text-end">Horas trabajadas</th>
                        <th class="text-end">Horas nocturnas</th>
                        <th>Estado</th>
                        <th width="200">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($turnos as $turno)
                        <tr>
                            <td>
                                <span class="badge border text-dark" style="background-color: {{ $turno->color }}">{{ $turno->nombre }}</span>
                            </td>
                            <td>
                                {{ $turno->horario() }}
                                @if ($turno->terminaAlDiaSiguiente())
                                    <span class="small text-muted">(termina al día siguiente)</span>
                                @endif
                            </td>
                            <td class="text-end">{{ (float) round($turno->duracionEnMinutos() / 60, 2) }}</td>
                            <td class="text-end">{{ $horasNocturnas[$turno->id] ?? '—' }}</td>
                            <td>{{ $turno->activo ? 'Activo' : 'Inactivo' }}</td>
                            <td>
                                <x-action-buttons :editRoute="'nomina.turnos.edit'" :deleteRoute="'nomina.turnos.destroy'" :id="$turno" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No hay turnos registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
@endsection
