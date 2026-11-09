@extends('layouts.app')

@section('title', 'Historial de la planilla')

@php
    use App\Services\AuditoriaPlanillaService;

    $pesos = fn ($valor): string => number_format((float) $valor, 0, ',', '.');
@endphp

@section('content')
    <x-page-header :title="'Historial del turno #'.$numero" icon="clock-history"
        subtitle="Cada guardado de la planilla con las filas y el valor de cada sección.">
        <a href="{{ route('control-planillas.index') }}" class="btn btn-light btn-sm">CONTROL DE PLANILLAS</a>
        <a href="{{ route('turnos.create', ['turno_busqueda' => $numero]) }}" class="btn btn-outline-light btn-sm">ABRIR PLANILLA</a>
    </x-page-header>

    <div class="table-responsive titulos-fijos">
        <table class="table table-bordered table-sm small">
            <thead class="table-primary">
                <tr>
                    <th>FECHA Y HORA</th>
                    <th>ACCIÓN</th>
                    <th>USUARIO</th>
                    @foreach (AuditoriaPlanillaService::SECCIONES as [$nombre])
                        <th class="text-center">{{ $nombre }}<br><span class="fw-normal">filas / valor</span></th>
                    @endforeach
                    <th class="text-end">TOTAL VENTAS</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($registros as $registro)
                    <tr @class(['table-danger' => $registro->alerta])>
                        <td class="text-nowrap">{{ $registro->created_at->format('d/m/Y H:i:s') }}</td>
                        <td>{{ mb_strtoupper($registro->accion) }}</td>
                        <td>{{ $registro->user?->name }}</td>
                        @foreach (array_keys(AuditoriaPlanillaService::SECCIONES) as $seccion)
                            <td class="text-end text-nowrap">
                                {{ $registro->resumen[$seccion]['filas'] ?? 0 }} / {{ $pesos($registro->resumen[$seccion]['valor'] ?? 0) }}
                            </td>
                        @endforeach
                        <td class="text-end">{{ $pesos($registro->resumen['total_ventas'] ?? 0) }}</td>
                    </tr>
                    @if ($registro->alerta)
                        <tr class="table-danger">
                            <td colspan="{{ count(AuditoriaPlanillaService::SECCIONES) + 4 }}" class="fw-semibold">{{ $registro->alerta }}</td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="{{ count(AuditoriaPlanillaService::SECCIONES) + 4 }}" class="text-center text-muted">
                            Sin guardados registrados. El historial de guardados se registra desde el 09/11/2026.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
