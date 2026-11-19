@extends('layouts.app')

@section('title', 'Control de vacaciones')

@php
    $dias = fn (float $valor): string => rtrim(rtrim(number_format($valor, 2, ',', '.'), '0'), ',');
    $semaforo = [
        'rojo' => ['text-bg-danger', 'Dos periodos o más acumulados'],
        'amarillo' => ['text-bg-warning', 'Un periodo cumplido pendiente'],
        'verde' => ['text-bg-success', 'Al día'],
    ];
@endphp

@section('content')
    <x-page-header title="Control de vacaciones" icon="sun"
        subtitle="15 días hábiles por cada año trabajado (360 días). Causadas, tomadas (disfrutadas o en dinero) y saldo por empleado." />

    <div class="d-flex flex-wrap gap-3 small mb-2">
        @foreach ($semaforo as [$clase, $texto])
            <span><span class="badge {{ $clase }}">&nbsp;</span> {{ $texto }}</span>
        @endforeach
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-primary">
                <tr>
                    <th>Empleado</th>
                    <th>Ingreso</th>
                    <th class="text-end">Días laborados</th>
                    <th class="text-end">Periodos cumplidos</th>
                    <th class="text-end">Días causados</th>
                    <th class="text-end">Días tomados</th>
                    <th class="text-end">Saldo</th>
                    <th class="text-center">Estado</th>
                    <th class="text-end"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($filas as $fila)
                    <tr>
                        <td class="fw-semibold">{{ $fila['empleado']->nombre_completo }}<div class="small text-muted">{{ $fila['empleado']->cargo }}</div></td>
                        <td>{{ $fila['empleado']->fecha_ingreso?->format('d/m/Y') }}</td>
                        <td class="text-end">{{ number_format($fila['dias_laborados'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ $fila['periodos'] }}</td>
                        <td class="text-end">{{ $dias($fila['causados']) }}</td>
                        <td class="text-end">{{ $fila['tomados'] }}</td>
                        <td class="text-end fw-bold">{{ $dias($fila['saldo']) }}</td>
                        <td class="text-center"><span class="badge {{ $semaforo[$fila['semaforo']][0] }}">{{ $semaforo[$fila['semaforo']][1] }}</span></td>
                        <td class="text-end"><a href="{{ route('nomina.vacaciones.show', $fila['empleado']) }}" class="btn btn-sm btn-primary"><i class="bi bi-calendar-plus" aria-hidden="true"></i> Registrar / ver</a></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">No hay empleados activos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
