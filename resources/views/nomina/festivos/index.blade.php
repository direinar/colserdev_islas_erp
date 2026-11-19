@extends('layouts.app')

@section('title', 'Festivos')

@section('content')
    <div class="container">

        <x-page-header title="Festivos" icon="calendar-event"
            subtitle="Días festivos que generan recargo dominical/festivo (los domingos se reconocen solos).">
            <form method="GET" action="{{ route('nomina.festivos.index') }}" class="d-flex gap-2 align-items-center">
                <label for="filtro-anio" class="visually-hidden">Año</label>
                <select id="filtro-anio" name="anio" class="form-select" onchange="this.form.submit()">
                    @foreach (range(now()->year - 2, now()->year + 2) as $opcion)
                        <option value="{{ $opcion }}" @selected($anio === $opcion)>{{ $opcion }}</option>
                    @endforeach
                </select>
            </form>
        </x-page-header>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @include('nomina.partials.errores-formulario')

        <div class="row">
            <div class="col-lg-8 mb-3">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead class="table-primary">
                            <tr>
                                <th>Fecha</th>
                                <th>Día</th>
                                <th>Festivo</th>
                                <th width="120">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($festivos as $festivo)
                                <tr>
                                    <td>{{ $festivo->fecha->format('d/m/Y') }}</td>
                                    <td class="text-capitalize">{{ $festivo->fecha->locale('es')->dayName }}</td>
                                    <td>{{ $festivo->nombre }}</td>
                                    <td>
                                        <x-action-buttons :deleteRoute="'nomina.festivos.destroy'" :id="$festivo" />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">No hay festivos registrados para {{ $anio }}.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-3">
                    <div class="card-header fw-semibold">Festivos de ley</div>
                    <div class="card-body">
                        <p class="small text-muted">Carga los 18 festivos de Colombia del año (Ley Emiliani y Semana Santa). Los ya registrados no se duplican.</p>
                        <form method="POST" action="{{ route('nomina.festivos.generar') }}">
                            @csrf
                            <input type="hidden" name="anio" value="{{ $anio }}">
                            <button type="submit" class="btn btn-primary w-100">Cargar festivos de {{ $anio }}</button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header fw-semibold">Agregar un festivo</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('nomina.festivos.store') }}">
                            @csrf
                            <div class="mb-3">
                                <label for="campo-fecha" class="form-label">Fecha</label>
                                <input id="campo-fecha" type="date" name="fecha" class="form-control" value="{{ old('fecha') }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="campo-nombre" class="form-label">Nombre</label>
                                <input id="campo-nombre" type="text" name="nombre" class="form-control" value="{{ old('nombre') }}" required>
                            </div>
                            <button type="submit" class="btn btn-outline-primary w-100">Agregar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
