@extends('layouts.app')

@section('title', 'Nueva liquidación de contrato')

@section('content')
    @php($dinero = fn (float $valor): string => '$ '.number_format($valor, 0, ',', '.'))

    <div class="container">
        <x-page-header title="Nueva liquidación de contrato" icon="file-earmark-person"
            subtitle="El sistema calcula los días (DAYS360), la base (promedio de los últimos 12 meses liquidados) y las prestaciones.">
            <a href="{{ route('nomina.liquidaciones-contrato.index') }}" class="btn btn-outline-light btn-sm">Volver</a>
        </x-page-header>

        @include('nomina.partials.errores-formulario')

        <form method="GET" action="{{ route('nomina.liquidaciones-contrato.create') }}" class="card mb-3">
            <div class="card-body row g-2 align-items-end">
                <div class="col-md-8">
                    <label for="empleado" class="form-label fw-semibold">1. Empleado a liquidar</label>
                    <select id="empleado" name="empleado" class="form-select" onchange="this.form.submit()" required>
                        <option value="">Elija el empleado…</option>
                        @foreach ($empleados as $opcion)
                            <option value="{{ $opcion->id }}" @selected($empleado?->id === $opcion->id)>
                                {{ $opcion->nombre_completo }} — {{ $opcion->cargo }}{{ $opcion->activo ? '' : ' (retirado)' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>

        @if ($empleado)
            <form method="POST" action="{{ route('nomina.liquidaciones-contrato.store') }}" class="card">
                @csrf
                <input type="hidden" name="empleado_id" value="{{ $empleado->id }}">
                <div class="card-header fw-semibold">2. Datos del retiro de {{ $empleado->nombre_completo }}</div>
                <div class="card-body">
                    <div class="row g-3 mb-2">
                        <div class="col-md-3">
                            <span class="form-label d-block small text-muted">Fecha de ingreso</span>
                            <strong>{{ $empleado->fecha_ingreso?->format('d/m/Y') ?? 'Sin registrar' }}</strong>
                        </div>
                        <div class="col-md-3">
                            <span class="form-label d-block small text-muted">Sueldo básico</span>
                            <strong>{{ $dinero((float) $empleado->salario_basico) }}</strong>
                        </div>
                        <div class="col-md-6">
                            <span class="form-label d-block small text-muted">Salario base de liquidación (estimado hoy)</span>
                            <strong>{{ $dinero((float) $baseSugerida) }}</strong>
                            <span class="small text-muted">· promedio de los últimos 12 meses liquidados, o sueldo + auxilio si no hay historial</span>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="fecha_retiro" class="form-label">Fecha de retiro</label>
                            <input type="date" id="fecha_retiro" name="fecha_retiro" class="form-control" required
                                value="{{ old('fecha_retiro', $empleado->fecha_retiro?->toDateString() ?? now()->toDateString()) }}">
                        </div>
                        <div class="col-md-5">
                            <label for="motivo_retiro" class="form-label">Motivo del retiro</label>
                            <input type="text" id="motivo_retiro" name="motivo_retiro" class="form-control" list="motivos" required maxlength="150"
                                value="{{ old('motivo_retiro', $empleado->motivo_retiro) }}">
                            <datalist id="motivos">
                                @foreach ($motivos as $motivo)
                                    <option value="{{ $motivo }}">
                                @endforeach
                            </datalist>
                        </div>
                    </div>
                    <h3 class="h6 mt-4">Salarios pendientes del último periodo</h3>
                    <div class="row g-3">
                        @foreach (['sueldo_pendiente' => 'Sueldo pendiente', 'recargos_nocturnos' => 'Recargos nocturnos', 'recargos_festivos' => 'Recargos por festivos', 'auxilio_transporte' => 'Auxilio de transporte', 'otras_deducciones' => 'Otras deducciones (faltantes, anticipos)'] as $campo => $etiqueta)
                            <div class="col-md-4 col-lg">
                                <label for="{{ $campo }}" class="form-label">{{ $etiqueta }}</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" id="{{ $campo }}" name="{{ $campo }}" class="form-control" min="0" step="1" value="{{ old($campo) }}">
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <label for="observaciones" class="form-label mt-3">Observaciones</label>
                    <textarea id="observaciones" name="observaciones" class="form-control" rows="2">{{ old('observaciones') }}</textarea>
                    @if ($empleado->activo)
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" value="1" id="registrar_retiro" name="registrar_retiro" @checked(old('registrar_retiro', true))>
                            <label class="form-check-label" for="registrar_retiro">Registrar el retiro en la ficha del empleado (queda inactivo con esta fecha y motivo)</label>
                        </div>
                    @endif
                </div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-calculator" aria-hidden="true"></i> Liquidar</button>
                </div>
            </form>
        @endif
    </div>
@endsection
