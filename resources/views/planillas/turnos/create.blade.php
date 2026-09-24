@extends('layouts.app')

@section('content')

    {{-- BÚSQUEDA: por fecha + número de turno, o por número de turno solo --}}
    <div class="mb-3 pastel-section d-flex flex-wrap justify-content-between align-items-end gap-3">

        <div class="d-flex flex-wrap align-items-end gap-3">

            <form method="GET" action="{{ route('turnos.create') }}" id="buscar-turno-form"
                class="d-flex flex-wrap align-items-end gap-2 mb-0">
                <div>
                    <label class="form-label small mb-0">Fecha búsqueda</label>
                    <input type="date" name="fecha" id="buscar-turno-fecha" class="form-control form-control-sm"
                        value="{{ request('fecha', $searchFecha ?? date('Y-m-d')) }}">
                </div>
                <div>
                    <label class="form-label small mb-0">Turno búsqueda</label>
                    <select name="numero_turno" id="buscar-turno-numero" class="form-select form-select-sm"
                        style="min-width: 140px" onchange="document.getElementById('buscar-turno-form').submit()">
                        <option value="">-- Seleccione --</option>
                        @foreach ($turnosDelDia as $numero)
                            <option value="{{ $numero }}" @selected((string) request('numero_turno') === (string) $numero)>
                                Turno {{ $numero }}
                            </option>
                        @endforeach
                    </select>
                    @if ($turnosDelDia->isEmpty())
                        <small class="text-muted d-block">Sin turnos registrados esta fecha</small>
                    @endif
                </div>
            </form>

            <form method="GET" action="{{ route('turnos.create') }}" id="buscar-turno-solo-form"
                class="d-flex align-items-end gap-2 mb-0">
                <div>
                    <label class="form-label small mb-0">Turno búsqueda</label>
                    <input type="number" name="turno_busqueda" min="1" class="form-control form-control-sm"
                        style="width: 140px" placeholder="N° de turno" value="{{ request('turno_busqueda') }}">
                </div>
            </form>

        </div>

        <div class="d-flex align-items-end gap-2">
            <button type="submit" form="buscar-turno-form" class="btn btn-sm btn-outline-primary">Buscar</button>
            <button type="submit" form="buscar-turno-solo-form" class="btn btn-sm btn-outline-primary">Buscar por
                turno</button>
            <a href="{{ route('turnos.create') }}" class="btn btn-sm btn-outline-secondary">Nuevo</a>
            <button type="button" class="btn btn-sm btn-outline-warning" onclick="limpiarPlanilla()">Limpiar</button>
            @if ($puedeGuardar ?? true)
                <button type="submit" form="turno-form" class="btn btn-sm btn-primary">Guardar</button>
            @endif
        </div>

    </div>

    <script>
        // Al cambiar la fecha, recargar la búsqueda para refrescar los turnos disponibles ese día.
        document.getElementById('buscar-turno-fecha')?.addEventListener('change', function() {
            document.getElementById('buscar-turno-form')?.submit();
        });

        // Vacía todos los campos editables de la planilla (deja intactos los readonly/hidden)
        // y dispara los eventos que usa galones.js para recalcular totales en pantalla.
        function limpiarPlanilla() {
            Swal.fire({
                title: '¿Limpiar planilla?',
                text: 'Se borrarán todos los campos de la planilla. Esta acción no se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, limpiar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545',
                reverseButtons: true,
            }).then(result => {
                if (result.isConfirmed) {
                    ejecutarLimpiezaPlanilla();
                }
            });
        }

        function ejecutarLimpiezaPlanilla() {
            const form = document.getElementById('turno-form');
            if (!form) return;

            form.querySelectorAll('input, select, textarea').forEach(el => {
                if (el.type === 'hidden' || el.type === 'checkbox' || el.type === 'radio' || el.readOnly || el
                    .disabled) {
                    return;
                }

                if (el.tagName === 'SELECT') {
                    el.selectedIndex = 0;
                } else {
                    el.value = '';
                }

                el.dispatchEvent(new Event('input', {
                    bubbles: true
                }));
                el.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            });
        }
    </script>

    <form method="POST" action="{{ route('turnos.store') }}" id="turno-form">
        @csrf

        {{-- HEADER --}}

        <div class="pastel-section mb-3">

            <div class="row align-items-center">

                <div class="col-md-6">

                    <h4 class="mb-0">
                        PLANILLA DE TURNOS
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

        @php
            // Un turno guardado ya tiene su precio ligado (snapshot); uno nuevo
            // usa el precio vigente para hoy según la tabla de precios. Sin fallback a
            // config(): si no hay un registro de FuelPrice vigente, el precio es 0 para
            // forzar a que se registre el precio real en Precios de Combustible.
            $fechaPrecio = isset($turno) ? $turno->fecha : now();
            $precioCorriente = isset($turno)
                ? $turno->precio_corriente
                : (float) \App\Models\FuelPrice::activePriceOn('Gasolina', $fechaPrecio);
            $precioAcpm = isset($turno)
                ? $turno->precio_acpm
                : (float) \App\Models\FuelPrice::activePriceOn('ACPM', $fechaPrecio);
        @endphp

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

                    <div class="col-md-6">
                        @include('planillas.turnos.partials.transferencias')
                    </div>

                    <div class="col-md-6">
                        @include('planillas.turnos.partials.recaudos')
                    </div>

                </div>
                {{-- Fila inferior --}}
                <div class="row mt-3">

                    <div class="col-md-6">
                        @include('planillas.turnos.partials.gasolina_eds')
                    </div>

                    <div class="col-md-6">
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

        @php
            // Coincide con la regla del servidor: un turno revisado solo lo puede
            // seguir editando un administrador; para los demás se oculta "Guardar".
            $turnoRevisado = isset($turno) && $turno && $turno->revisado;
            $puedeGuardar = !$turnoRevisado || auth()->user()->isAdministrador();
        @endphp

        @if (!$puedeGuardar)
            <div class="pastel-section mt-3 text-end">
                <span class="badge bg-secondary">Planilla revisada: el registro está bloqueado</span>
            </div>
        @endif

    </form>

    {{-- Formulario independiente (no anidado) para marcar el turno como revisado --}}
    @if (isset($turno) && $turno && !$turno->revisado && auth()->user()->isAdministrador())
        <form method="POST" action="{{ route('turnos.revisar', $turno) }}" id="form-marcar-revisado" class="d-none">
            @csrf
        </form>
    @endif
@endsection
