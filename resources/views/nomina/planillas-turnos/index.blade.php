@extends('layouts.app')

@section('title', 'Planilla de turnos')

@section('content')
    <div class="container">

        <x-page-header title="Planilla de turnos" icon="calendar-week"
            subtitle="Programación mensual de turnos y liquidación de recargos nocturnos y dominicales/festivos." />

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @include('nomina.partials.errores-formulario')

        <div class="card mb-3 d-print-none">
            <div class="card-header fw-semibold">Nueva planilla</div>
            <div class="card-body">
                <form method="POST" action="{{ route('nomina.planillas-turnos.store') }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-3">
                        <label for="campo-mes" class="form-label">Mes</label>
                        <select id="campo-mes" name="mes" class="form-select" required>
                            @foreach (range(1, 12) as $mes)
                                <option value="{{ $mes }}" @selected((int) old('mes', $siguiente->month) === $mes)>
                                    {{ ucfirst(\Carbon\Carbon::create(2000, $mes, 1)->locale('es')->monthName) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="campo-anio" class="form-label">Año</label>
                        <input id="campo-anio" type="number" name="anio" class="form-control" min="2020" max="2100"
                            value="{{ old('anio', $siguiente->year) }}" required>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">Crear planilla</button>
                    </div>
                    <div class="col-md-4 small text-muted">
                        Incluye a los empleados activos marcados con «Sobresueldos y recargos».
                    </div>
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-primary">
                    <tr>
                        <th>Mes</th>
                        <th>Estado</th>
                        <th class="text-end">Turnos asignados</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($planillas as $planilla)
                        <tr>
                            <td>{{ $planilla->nombreDelMes() }}</td>
                            <td>
                                @if ($planilla->estaCerrada())
                                    <span class="badge text-bg-success">Cerrada</span>
                                @else
                                    <span class="badge text-bg-warning">Borrador</span>
                                @endif
                            </td>
                            <td class="text-end">{{ $planilla->asignaciones_count }}</td>
                            <td>
                                <div class="crud-actions">
                                    @unless ($planilla->estaCerrada())
                                        <a href="{{ route('nomina.planillas-turnos.edit', $planilla) }}" class="btn btn-action edit">Programar</a>
                                    @endunless
                                    <a href="{{ route('nomina.planillas-turnos.show', $planilla) }}" class="btn btn-action view">Turnos</a>
                                    <a href="{{ route('nomina.planillas-turnos.liquidacion', $planilla) }}" class="btn btn-action view">Recargos</a>
                                    @unless ($planilla->estaCerrada())
                                        <form method="POST" action="{{ route('nomina.planillas-turnos.destroy', $planilla) }}"
                                            onsubmit="return confirm('¿Eliminar la planilla y todos sus turnos?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-action delete">Eliminar</button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">No hay planillas registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
@endsection
