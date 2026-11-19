@extends('layouts.app')

@section('title', 'Empleados')

@section('content')
    <div class="container">

        <x-page-header title="Empleados" icon="people" subtitle="Datos de los trabajadores que alimentan la planilla de turnos y la nómina.">
            <a href="{{ route('nomina.empleados.create') }}" class="btn btn-light">Nuevo empleado</a>
        </x-page-header>

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form method="GET" action="{{ route('nomina.empleados.index') }}" class="row g-2 mb-3 d-print-none">
            <div class="col-md-3">
                <label for="filtro-estado" class="form-label">Estado</label>
                <select id="filtro-estado" name="estado" class="form-select" onchange="this.form.submit()">
                    <option value="activos" @selected($estado === 'activos')>Activos</option>
                    <option value="retirados" @selected($estado === 'retirados')>Retirados</option>
                    <option value="todos" @selected($estado === 'todos')>Todos</option>
                </select>
            </div>
            <div class="col-md-6">
                <label for="filtro-buscar" class="form-label">Buscar</label>
                <input id="filtro-buscar" type="search" name="buscar" class="form-control" value="{{ $buscar }}"
                    placeholder="Nombre, documento o cargo">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-primary">
                    <tr>
                        <th>Empleado</th>
                        <th>Documento</th>
                        <th>Cargo</th>
                        <th>Ingreso</th>
                        <th>Contrato</th>
                        <th class="text-end">Sueldo básico</th>
                        <th class="text-center">Aux. transporte</th>
                        <th class="text-center">Recargos</th>
                        <th width="250">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($empleados as $empleado)
                        <tr>
                            <td>
                                {{ $empleado->nombre_completo }}
                                <span class="badge text-bg-secondary">{{ $empleado->nombre_corto }}</span>
                                @unless ($empleado->activo)
                                    <span class="badge text-bg-danger">Retirado</span>
                                @endunless
                            </td>
                            <td>{{ $empleado->tipo_documento }} {{ $empleado->numero_documento }}</td>
                            <td>{{ $empleado->cargo }}</td>
                            <td>{{ $empleado->fecha_ingreso->format('d/m/Y') }}</td>
                            <td>
                                {{ App\Models\Nomina\Empleado::tiposContrato()[$empleado->tipo_contrato] ?? $empleado->tipo_contrato }}
                                @if ($empleado->fecha_vencimiento_contrato)
                                    <div class="small {{ $empleado->contratoPorVencer() ? 'text-danger fw-semibold' : 'text-muted' }}">
                                        Vence {{ $empleado->fecha_vencimiento_contrato->format('d/m/Y') }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-end">$ {{ number_format((float) $empleado->salario_basico, 0, ',', '.') }}</td>
                            <td class="text-center">{{ $empleado->recibe_auxilio_transporte ? 'Sí' : 'No' }}</td>
                            <td class="text-center">{{ $empleado->aplica_recargos ? 'Sí' : 'No' }}</td>
                            <td>
                                <x-action-buttons :showRoute="'nomina.empleados.show'" :editRoute="'nomina.empleados.edit'" :deleteRoute="'nomina.empleados.destroy'" :id="$empleado" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center">No hay empleados para mostrar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
@endsection
