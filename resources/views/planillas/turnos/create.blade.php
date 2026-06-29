@extends('layouts.app')

@section('content')

    {{-- BÚSQUEDA: cargar un turno existente por fecha + número --}}
    <form method="GET" action="{{ route('turnos.create') }}" class="mb-3 pastel-section">
        <div class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label small mb-0">Fecha</label>
                <input type="date" name="fecha" class="form-control form-control-sm"
                    value="{{ request('fecha', date('Y-m-d')) }}">
            </div>
            <div class="col-auto">
                <label class="form-label small mb-0">Turno</label>
                <input type="number" name="numero_turno" class="form-control form-control-sm" min="1"
                    value="{{ request('numero_turno') }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-outline-primary">Buscar</button>
                <a href="{{ route('turnos.create') }}" class="btn btn-sm btn-outline-secondary">Nuevo</a>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm">Guardar Turno</button>
            </div>
        </div>
    </form>

    <form method="POST" action="{{ route('turnos.store') }}">
        @csrf

        {{-- HEADER --}}

        <div class="pastel-section mb-3">

            <div class="row align-items-center">

                <div class="col-md-6">

                    <h4 class="mb-0">
                        PLANTILLA DE TURNOS
                    </h4>

                </div>

                <div class="col-md-6 text-end">

                    FECHA:

                    <input type="date" name="fecha" class="form-control form-control-sm d-inline-block w-auto"
                        value="{{ old('fecha', request('fecha', date('Y-m-d'))) }}" required>

                    TURNO:

                    {{-- Mostrar turno formateado y bloquear edición; enviar valor entero en campo hidden --}}
                    @php
                        $displayNumber = isset($turno) && $turno ? $turno->numero_turno : $nextNumber ?? 1;
                    @endphp
                    <input type="hidden" name="numero_turno" value="{{ $displayNumber }}">
                    <input type="text" class="form-control form-control-sm d-inline-block" style="width: 80px"
                        value="{{ str_pad($displayNumber, 3, '0', STR_PAD_LEFT) }}" readonly>

                </div>

            </div>

        </div>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- CONTENIDO --}}

        <div class="row">

            {{-- COLUMNA IZQUIERDA --}}

            <div class="col-lg-4">

                @include('planillas.turnos.partials.ventas')

                @include('planillas.turnos.partials.surtidores')

                @include('planillas.turnos.partials.lubricantes')

                @include('planillas.turnos.partials.resumen')

                @include('planillas.turnos.partials.sobrantes')

            </div>

            {{-- CENTRO --}}

            <div class="col-lg-8">

                {{-- Ocupa todo el ancho --}}
                @include('planillas.turnos.partials.medios_pago')

                {{-- Fila inferior --}}
                <div class="row mt-3">

                    <div class="col-md-4">
                        @include('planillas.turnos.partials.qr')
                    </div>

                    <div class="col-md-4">
                        @include('planillas.turnos.partials.transferencias')
                    </div>

                    <div class="col-md-4">
                        @include('planillas.turnos.partials.recaudos')
                    </div>

                </div>
                {{-- Fila inferior --}}
                <div class="row mt-3">

                    <div class="col-md-4">
                        @include('planillas.turnos.partials.gasolina_eds')
                    </div>

                    <div class="col-md-4">
                        @include('planillas.turnos.partials.varios')
                    </div>

                </div>

                {{-- Resumen y Recaudos Administración --}}
                <div class="row mt-3">
                    <div class="col-lg-6">
                        @include('planillas.turnos.partials.resumen_recibido_turno')
                    </div>
                    <div class="col-lg-6">
                        @include('planillas.turnos.partials.recaudos_admin')
                    </div>
                </div>
            </div>
        </div>


    </form>
@endsection
