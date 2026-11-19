@extends('layouts.app')

@section('title', 'Vacaciones de '.$empleado->nombre_completo)

@php
    $dias = fn (float $valor): string => rtrim(rtrim(number_format($valor, 2, ',', '.'), '0'), ',');
    $colores = ['rojo' => 'nm-rojo', 'amarillo' => 'nm-amarillo', 'verde' => 'nm-verde'];
@endphp

@section('content')
    @include('nomina.partials.estilos')

    <x-page-header :title="'Control de vacaciones · '.$empleado->nombre_completo" icon="sun"
        :subtitle="$empleado->cargo.' · Ingreso '.($empleado->fecha_ingreso?->format('d/m/Y') ?? '—')">
        <button type="button" class="btn btn-outline-light btn-sm" onclick="window.print()"><i class="bi bi-printer" aria-hidden="true"></i> Imprimir formato</button>
        <a href="{{ route('nomina.vacaciones.index') }}" class="btn btn-outline-light btn-sm">Volver</a>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success d-print-none">{{ session('success') }}</div>
    @endif
    @include('nomina.partials.errores-formulario')

    <div class="nm-kpis">
        <div class="nm-kpi nm-azul"><span class="nm-kpi-icono"><i class="bi bi-calendar3" aria-hidden="true"></i></span>
            <span><span class="nm-kpi-valor d-block">{{ number_format($control['dias_laborados'], 0, ',', '.') }}</span><span class="nm-kpi-titulo">Días laborados ({{ $control['periodos'] }} periodo(s))</span></span></div>
        <div class="nm-kpi nm-azul"><span class="nm-kpi-icono"><i class="bi bi-plus-circle" aria-hidden="true"></i></span>
            <span><span class="nm-kpi-valor d-block">{{ $dias($control['causados']) }}</span><span class="nm-kpi-titulo">Días causados</span></span></div>
        <div class="nm-kpi nm-morado"><span class="nm-kpi-icono"><i class="bi bi-dash-circle" aria-hidden="true"></i></span>
            <span><span class="nm-kpi-valor d-block">{{ $control['tomados'] }}</span><span class="nm-kpi-titulo">Días tomados</span></span></div>
        <div class="nm-kpi {{ $colores[$control['semaforo']] }}"><span class="nm-kpi-icono"><i class="bi bi-sun" aria-hidden="true"></i></span>
            <span><span class="nm-kpi-valor d-block">{{ $dias($control['saldo']) }}</span><span class="nm-kpi-titulo">Saldo de días hábiles</span></span></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4 d-print-none">
            <form method="POST" action="{{ route('nomina.vacaciones.store', $empleado) }}" class="card">
                @csrf
                <div class="card-header fw-semibold"><i class="bi bi-calendar-plus" aria-hidden="true"></i> Registrar vacaciones</div>
                <div class="card-body row g-2">
                    <div class="col-6">
                        <label for="fecha_inicio" class="form-label">Salida</label>
                        <input type="date" id="fecha_inicio" name="fecha_inicio" class="form-control" value="{{ old('fecha_inicio') }}" required>
                    </div>
                    <div class="col-6">
                        <label for="fecha_fin" class="form-label">Regreso</label>
                        <input type="date" id="fecha_fin" name="fecha_fin" class="form-control" value="{{ old('fecha_fin') }}">
                    </div>
                    <div class="col-6">
                        <label for="dias_disfrutados" class="form-label">Días disfrutados</label>
                        <input type="number" id="dias_disfrutados" name="dias_disfrutados" class="form-control" min="0" max="90" value="{{ old('dias_disfrutados') }}">
                    </div>
                    <div class="col-6">
                        <label for="dias_dinero" class="form-label">Días en dinero</label>
                        <input type="number" id="dias_dinero" name="dias_dinero" class="form-control" min="0" max="90" value="{{ old('dias_dinero') }}">
                    </div>
                    <div class="col-12">
                        <label for="periodo" class="form-label">Periodo causado</label>
                        <input type="text" id="periodo" name="periodo" class="form-control" maxlength="40" placeholder="Ej.: 2025-2026" value="{{ old('periodo') }}">
                    </div>
                    <div class="col-12">
                        <label for="observaciones" class="form-label">Observaciones</label>
                        <input type="text" id="observaciones" name="observaciones" class="form-control" maxlength="500" value="{{ old('observaciones') }}">
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save" aria-hidden="true"></i> Registrar</button>
                </div>
            </form>
            <p class="small text-muted mt-2">El pago se liquida en la nómina del mes (columnas «Vac. disfrut.» y «Vac. dinero»). Este formato lleva el control de los días.</p>
        </div>
        <div class="col-lg-8">
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle">
                    <thead class="table-primary">
                        <tr><th>Salida</th><th>Regreso</th><th>Periodo</th><th class="text-end">Disfrutados</th><th class="text-end">En dinero</th><th>Observaciones</th><th class="d-print-none"></th></tr>
                    </thead>
                    <tbody>
                        @forelse ($empleado->vacaciones as $vacacion)
                            <tr>
                                <td>{{ $vacacion->fecha_inicio->format('d/m/Y') }}</td>
                                <td>{{ $vacacion->fecha_fin?->format('d/m/Y') }}</td>
                                <td>{{ $vacacion->periodo }}</td>
                                <td class="text-end">{{ $vacacion->dias_disfrutados ?: '' }}</td>
                                <td class="text-end">{{ $vacacion->dias_dinero ?: '' }}</td>
                                <td class="small">{{ $vacacion->observaciones }}</td>
                                <td class="text-center d-print-none">
                                    <form method="POST" action="{{ route('nomina.vacaciones.destroy', [$empleado, $vacacion]) }}" data-sin-aviso onsubmit="return confirm('¿Eliminar este registro?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Eliminar"><i class="bi bi-trash" aria-hidden="true"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-3">Sin vacaciones registradas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-none d-print-block mt-5">
                <table style="width:100%;"><tr>
                    <td style="width:50%; padding-right:20px;"><div style="border-top:1px solid #000;">{{ $empleado->nombre_completo }}</div></td>
                    <td style="width:50%;"><div style="border-top:1px solid #000;">Por la empresa</div></td>
                </tr></table>
            </div>
        </div>
    </div>
@endsection
