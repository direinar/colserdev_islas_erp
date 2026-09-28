@extends('layouts.app')

@section('content')

    @php
        // Una planilla revisada queda bloqueada para todos; el administrador puede
        // devolverla a PENDIENTE DE REVISIÓN para corregirla.
        $turnoRevisado = isset($turno) && $turno && $turno->revisado;
        $puedeGuardar = ! $turnoRevisado;
    @endphp

    {{-- BÚSQUEDA: por fecha (lista las planillas del día) o por número de turno --}}
    <div class="mb-3 pastel-section">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">

            <div class="d-flex flex-wrap align-items-end gap-3">
                <form method="GET" action="{{ route('turnos.create') }}" id="buscar-fecha-form" class="mb-0">
                    <label class="form-label small mb-0" for="buscar-turno-fecha">Buscar por fecha</label>
                    <input type="date" name="buscar_fecha" id="buscar-turno-fecha" class="form-control form-control-sm"
                        value="{{ $buscarFecha }}" onchange="this.form.submit()">
                </form>

                <form method="GET" action="{{ route('turnos.create') }}" id="buscar-turno-solo-form"
                    class="d-flex flex-wrap align-items-end gap-2 mb-0">
                    <div>
                        <label class="form-label small mb-0" for="turno-busqueda">Turno búsqueda</label>
                        <input type="number" name="turno_busqueda" id="turno-busqueda" min="1"
                            class="form-control form-control-sm" style="width: 140px; max-width: 100%" placeholder="N° de turno"
                            value="{{ request('turno_busqueda') }}">
                    </div>
                    <button type="submit" class="btn btn-sm btn-outline-primary">Buscar por turno</button>
                </form>
            </div>

            <div class="d-flex flex-wrap align-items-end gap-2">
                <a href="{{ route('turnos.create') }}" class="btn btn-sm btn-outline-secondary">Nuevo</a>
                @if ($puedeGuardar)
                    <button type="button" class="btn btn-sm btn-outline-warning" onclick="limpiarPlanilla()">Limpiar</button>
                    <button type="submit" form="turno-form" class="btn btn-sm btn-primary">Guardar</button>
                @endif
            </div>
        </div>

        @if ($buscarFecha)
            <div class="mt-2">
                <small class="text-muted d-block mb-1">Planillas del
                    {{ \Illuminate\Support\Carbon::parse($buscarFecha)->format('d/m/Y') }}:</small>
                @forelse ($turnosDeFecha as $turnoFecha)
                    <a href="{{ route('turnos.create', ['turno_busqueda' => $turnoFecha->numero_turno]) }}"
                        class="btn btn-sm {{ $turnoFecha->revisado ? 'btn-outline-success' : 'btn-outline-danger' }} me-1 mb-1">
                        Turno {{ str_pad($turnoFecha->numero_turno, 3, '0', STR_PAD_LEFT) }}
                        · {{ $turnoFecha->revisado ? 'Revisado' : 'Pendiente' }}
                        @if ($turnoFecha->nombre_vendedor)
                            · {{ $turnoFecha->nombre_vendedor }}
                        @endif
                    </a>
                @empty
                    <span class="text-muted small">No hay planillas registradas en esta fecha.</span>
                @endforelse
            </div>
        @endif
    </div>

    @if ($turnoRevisado)
        <div class="alert alert-success d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <strong>PLANILLA REVISADA.</strong> No se puede modificar ni alimentar.
                Revisada por {{ $turno->revisado_por }}{{ $turno->revisado_at ? ' el '.$turno->revisado_at->format('d/m/Y H:i') : '' }}.
            </div>
            @if (auth()->user()->isAdministrador())
                <form method="POST" action="{{ route('turnos.reabrir', $turno) }}" class="mb-0"
                    onsubmit="return confirm('¿Volver la planilla a PENDIENTE DE REVISIÓN para corregirla?');">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-warning">Volver a pendiente de revisión</button>
                </form>
            @endif
        </div>
    @endif

    <script>
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
        {{-- Planilla revisada: todos los campos y botones de la planilla quedan deshabilitados. --}}
        <fieldset @disabled(! $puedeGuardar) style="min-width: 0;">

        {{-- HEADER --}}

        <div class="pastel-section mb-3">

            <div class="row align-items-center g-2">

                <div class="col-md-6">

                    <h4 class="mb-0">
                        PLANILLA DE TURNOS
                    </h4>

                </div>

                <div class="col-md-6 d-flex flex-wrap align-items-center gap-2 justify-content-md-end">

                    FECHA:

                    <input type="date" name="fecha" class="form-control form-control-sm d-inline-block w-auto"
                        value="{{ old('fecha', isset($turno) && $turno ? $turno->fecha->toDateString() : request('fecha', date('Y-m-d'))) }}" required>

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

        </fieldset>

        @if (! $puedeGuardar)
            <div class="alert alert-danger mt-3 mb-0 text-center fs-4 fw-bold text-danger border-2 border-danger">
                <i class="bi bi-lock-fill"></i> Planilla REVISADA: el registro está bloqueado
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
