@extends('layouts.app')

@section('content')

    @php
        // Una planilla revisada queda bloqueada para todos; el administrador puede
        // devolverla a PENDIENTE DE REVISIÓN para corregirla.
        $turnoRevisado = isset($turno) && $turno && $turno->revisado;
        // El islero y el jefe de patios solo modifican las planillas que ellos registraron.
        $turnoDeOtro = isset($turno) && $turno && ! $turnoRevisado && ! $turno->editablePor(auth()->user());
        $puedeGuardar = ! $turnoRevisado && ! $turnoDeOtro;
        $turnoAbierto = isset($turno) && $turno && $turno->exists && $turno->estaAbierta();
    @endphp

    <x-page-header title="Planilla de turnos" icon="journal-check"
        subtitle="Ventas, lecturas, formas de pago y cuadre de cada turno.">
        <a href="{{ route('turnos.create') }}" class="btn btn-outline-light btn-sm">
            <i class="bi bi-file-earmark-plus" aria-hidden="true"></i> Nuevo
        </a>
        @if ($turno && $puedeGuardar)
            <button type="button" class="btn btn-outline-light btn-sm" onclick="limpiarPlanilla()">
                <i class="bi bi-eraser" aria-hidden="true"></i> Limpiar
            </button>
            <button type="submit" form="turno-form" class="btn btn-light btn-sm">
                <i class="bi bi-save" aria-hidden="true"></i> Guardar
            </button>
            @if ($turnoAbierto)
                <button type="submit" form="turno-form" name="cerrar" value="1" class="btn btn-warning btn-sm"
                    onclick="return confirm('¿Cerrar el turno? Se guarda la planilla y se exigen las lecturas finales de todas sus mangueras. Las mangueras quedarán libres para el siguiente turno.');">
                    <i class="bi bi-lock" aria-hidden="true"></i> CERRAR TURNO
                </button>
            @endif
        @endif
    </x-page-header>

    {{-- BÚSQUEDA: por fecha (lista las planillas del día) o por número de turno --}}
    <div class="mb-3 pastel-section d-print-none">
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
                            class="form-control form-control-sm" style="width: 140px; max-width: 100%; text-align: left !important;" placeholder="N° de turno"
                            value="{{ request('turno_busqueda') }}">
                    </div>
                    <button type="submit" class="btn btn-sm btn-outline-primary">Buscar por turno</button>
                </form>
            </div>
        </div>

        @if ($buscarFecha)
            <div class="mt-2">
                <small class="text-muted d-block mb-1">Planillas del
                    {{ \Illuminate\Support\Carbon::parse($buscarFecha)->format('d/m/Y') }}:</small>
                @forelse ($turnosDeFecha as $turnoFecha)
                    <a href="{{ route('turnos.create', ['turno_busqueda' => $turnoFecha->numero_turno]) }}"
                        class="btn btn-sm {{ $turnoFecha->revisado ? 'btn-outline-success' : ($turnoFecha->estaAbierta() ? 'btn-outline-warning text-dark' : 'btn-outline-danger') }} me-1 mb-1">
                        Turno {{ str_pad($turnoFecha->numero_turno, 3, '0', STR_PAD_LEFT) }}
                        @if ($turnoFecha->turno_dia)
                            ({{ $turnoFecha->turno_dia }}° del día)
                        @endif
                        · {{ $turnoFecha->revisado ? 'Revisado' : ($turnoFecha->estaAbierta() ? 'Abierto' : 'Pendiente') }}
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

        // CARTERA - CRÉDITO DIRECTO, RECAUDOS Y ANTICIPOS POR ISLAS y RECAUDOS POR
        // ADMINISTRACIÓN: no se digita el valor sin haber SELECCIONADO el cliente; en PAGOS
        // ELECTRONICOS Y TRANSFERENCIAS, sin haber SELECCIONADO el concepto.
        const SECCIONES_CON_CLIENTE = [{
                valor: '.cartera-valor-input',
                cliente: '.cartera-cliente-select',
                titulo: 'CARTERA - CRÉDITO DIRECTO'
            },
            {
                valor: '.recaudo-valor',
                cliente: '.recaudos-cliente',
                titulo: 'RECAUDOS Y ANTICIPOS POR ISLAS'
            },
            {
                valor: '.recaudo-admin-valor',
                cliente: '.recaudos-admin-responsable',
                titulo: 'RECAUDOS POR ADMINISTRACIÓN'
            },
            {
                valor: '.qr-valor',
                cliente: '.qr-concepto',
                titulo: 'PAGOS ELECTRONICOS Y TRANSFERENCIAS',
                campo: 'concepto'
            },
        ];

        function seccionDelValor(input) {
            return SECCIONES_CON_CLIENTE.find(s => input.matches?.(s.valor));
        }

        function clienteDeLaFila(input, seccion) {
            return input.closest('tr')?.querySelector(seccion.cliente);
        }

        function avisarSinCliente(seccion, select) {
            const campo = seccion.campo ?? 'cliente';

            Swal.fire({
                title: 'Seleccione el ' + campo,
                text: 'En ' + seccion.titulo + ' primero debe SELECCIONAR el ' + campo + ' y luego digitar el valor.',
                icon: 'warning',
                confirmButtonText: 'Entendido',
            }).then(() => select?.focus());
        }

        // Campos que exigen otro dato de la misma fila antes de alimentarse:
        // CARTERA - CRÉDITO DIRECTO: ni cliente ni valor sin el No. de FACTURA.
        // RECAUDOS POR ADMINISTRACIÓN: ni cliente ni valor sin el BANCO/CAJA.
        const REQUISITOS_PREVIOS = [{
                campos: '.cartera-cliente-select, .cartera-valor-input',
                requisito: '.cartera-no-input',
                titulo: 'Digite el No. de factura',
                texto: 'En CARTERA - CRÉDITO DIRECTO primero debe digitar el No. de FACTURA y luego el cliente y el valor.',
            },
            {
                campos: '.recaudos-admin-responsable, .recaudo-admin-valor',
                requisito: '.recaudos-admin-banco',
                titulo: 'Seleccione el banco',
                texto: 'En RECAUDOS POR ADMINISTRACIÓN primero debe SELECCIONAR el BANCO/CAJA y luego el cliente y el valor.',
            },
        ];
        const CAMPOS_CON_REQUISITO = REQUISITOS_PREVIOS.map(regla => regla.campos).join(', ');

        // Devuelve la regla y el campo previo vacío de la fila, o null si se puede alimentar.
        function requisitoFaltante(campo) {
            if (campo.disabled) return null;

            const regla = REQUISITOS_PREVIOS.find(r => campo.matches?.(r.campos));
            const requisito = regla && campo.closest('tr')?.querySelector(regla.requisito);

            return requisito && requisito.value.trim() === '' ? { regla, requisito } : null;
        }

        function avisarRequisito({ regla, requisito }) {
            Swal.fire({
                title: regla.titulo,
                text: regla.texto,
                icon: 'warning',
                confirmButtonText: 'Entendido',
            }).then(() => requisito.focus());
        }

        // Con el mouse: evita que se abra la lista de clientes.
        document.addEventListener('mousedown', function(event) {
            const faltante = requisitoFaltante(event.target);
            if (faltante) {
                event.preventDefault();
                avisarRequisito(faltante);
            }
        });

        // Con el teclado (Tab): se registra antes que la regla del cliente para avisar primero el requisito.
        document.addEventListener('focusin', function(event) {
            const faltante = requisitoFaltante(event.target);
            if (faltante) {
                event.stopImmediatePropagation();
                event.target.blur();
                avisarRequisito(faltante);
            }
        });

        // Respaldo (p. ej. si se pega un valor): deshace lo alimentado sin el requisito.
        document.addEventListener('input', function(event) {
            if (requisitoFaltante(event.target)) event.target.value = '';
        });
        document.addEventListener('change', function(event) {
            if (requisitoFaltante(event.target)) event.target.value = '';
        });

        document.addEventListener('focusin', function(event) {
            const seccion = seccionDelValor(event.target);
            if (!seccion || event.target.disabled || event.target.readOnly) return;

            const select = clienteDeLaFila(event.target, seccion);
            if (select && !select.value) {
                event.target.blur();
                avisarSinCliente(seccion, select);
            }
        });

        // Respaldo (p. ej. si se pega un valor): borra lo digitado sin cliente.
        document.addEventListener('input', function(event) {
            const seccion = seccionDelValor(event.target);
            if (!seccion) return;

            const select = clienteDeLaFila(event.target, seccion);
            if (select && !select.value && event.target.value !== '') {
                event.target.value = '';
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('turno-form')?.addEventListener('submit', function(event) {
                for (const campo of this.querySelectorAll(CAMPOS_CON_REQUISITO)) {
                    const lleno = campo.tagName === 'SELECT' ? campo.value !== '' :
                        (Number(String(campo.value).replace(/\./g, '').replace(/,/g, '.')) || 0) !== 0;
                    const faltante = requisitoFaltante(campo);

                    if (lleno && faltante) {
                        event.preventDefault();
                        event.stopImmediatePropagation();
                        avisarRequisito(faltante);
                        return;
                    }
                }

                for (const seccion of SECCIONES_CON_CLIENTE) {
                    for (const input of this.querySelectorAll(seccion.valor)) {
                        if (input.disabled) continue;

                        const valor = Number(String(input.value).replace(/\./g, '').replace(/,/g, '.')) || 0;
                        const select = clienteDeLaFila(input, seccion);

                        if (valor !== 0 && select && !select.value) {
                            event.preventDefault();
                            event.stopImmediatePropagation();
                            avisarSinCliente(seccion, select);
                            return;
                        }
                    }
                }
            }, true);
        });

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

    @if (! $turno)
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @include('planillas.turnos.partials.apertura')
    @else

    {{-- El jefe de patios cierra la planilla de un islero que no la cerró: solo las lecturas finales. --}}
    @if ($turnoAbierto && $turnoDeOtro && $turno->cerrablePor(auth()->user()))
        <form method="POST" action="{{ route('turnos.cerrar', $turno) }}" class="pastel-section mb-3"
            onsubmit="return confirm('¿Cerrar el turno #{{ $turno->numero_turno }} con estas lecturas finales?');">
            @csrf
            <h2 class="h6 fw-bold">CERRAR TURNO DE {{ mb_strtoupper($turno->user?->name ?? $turno->nombre_vendedor) }}</h2>
            <p class="small text-muted mb-2">El islero no cerró la planilla. Digite las lecturas finales de sus mangueras para liberarlas.</p>
            <div class="row g-2">
                @foreach ($turno->surtidores as $surtidor)
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-0" for="final-{{ $loop->index }}">{{ $surtidor->manguera }}
                            (inicial {{ number_format($surtidor->lectura_inicial, 3, '.', ',') }})</label>
                        <input type="text" name="finales[{{ $surtidor->manguera }}]" id="final-{{ $loop->index }}"
                            class="form-control form-control-sm" inputmode="decimal" required
                            value="{{ old('finales.'.$surtidor->manguera, $surtidor->lectura_final === null ? '' : number_format($surtidor->lectura_final, 3, '.', ',')) }}">
                    </div>
                @endforeach
            </div>
            <div class="text-end mt-2">
                <button type="submit" class="btn btn-warning btn-sm"><i class="bi bi-lock" aria-hidden="true"></i> CERRAR TURNO</button>
            </div>
        </form>
    @endif

    <form method="POST" action="{{ route('turnos.store') }}" id="turno-form">
        @csrf
        {{-- Planilla revisada: todos los campos y botones de la planilla quedan deshabilitados. --}}
        <fieldset @disabled(! $puedeGuardar) style="min-width: 0;">

        {{-- HEADER --}}

        <div class="pastel-section mb-3">

            <div class="row align-items-center g-2">

                <div class="col-md-6">

                    <h2 class="h5 mb-0 fw-bold">
                        DATOS DEL TURNO
                    </h2>

                </div>

                <div class="col-md-6 d-flex flex-wrap align-items-center gap-2 justify-content-md-end">

                    <label class="fw-semibold mb-0" for="turno-fecha">FECHA:</label>

                    <input type="date" name="fecha" id="turno-fecha" class="form-control form-control-sm d-inline-block w-auto dato-turno-destacado"
                        value="{{ old('fecha', isset($turno) && $turno ? $turno->fecha->toDateString() : request('fecha', date('Y-m-d'))) }}" required>

                    <label class="fw-semibold mb-0" for="turno-numero">TURNO:</label>

                    {{-- Mostrar turno formateado y bloquear edición; enviar valor entero en campo hidden --}}
                    @php
                        $displayNumber = isset($turno) && $turno ? $turno->numero_turno : $nextNumber ?? 1;
                    @endphp
                    <input type="hidden" name="numero_turno" value="{{ $displayNumber }}">
                    <input type="text" id="turno-numero" class="form-control form-control-sm d-inline-block dato-turno-destacado" style="width: 90px"
                        value="{{ str_pad($displayNumber, 3, '0', STR_PAD_LEFT) }}" readonly>

                </div>

            </div>

            @if ($turno->exists)
                <div class="d-flex flex-wrap gap-3 mt-2 small">
                    <span>
                        <span class="badge {{ $turnoAbierto ? 'bg-warning text-dark' : 'bg-secondary' }} fs-6">
                            {{ $turnoAbierto ? 'TURNO ABIERTO' : 'TURNO CERRADO' }}
                        </span>
                    </span>
                    @if ($turno->turno_dia)
                        <span><strong>TURNO DEL DÍA:</strong> {{ $turno->turno_dia }}</span>
                    @endif
                    @if ($turno->hora_inicio)
                        <span><strong>INICIO:</strong> {{ substr($turno->hora_inicio, 0, 5) }}</span>
                    @endif
                    @if ($turno->cerrado_at)
                        <span><strong>CERRADO:</strong> {{ $turno->cerrado_at->format('d/m/Y H:i') }} por {{ $turno->cerrado_por }}</span>
                    @endif
                    <span><strong>MANGUERAS:</strong> {{ $turno->surtidores->pluck('manguera')->implode(', ') }}</span>
                </div>
            @endif

        </div>

        <style>
            /* Fecha y número de turno resaltados para identificar la planilla de un vistazo. */
            /* custom.css fija el color de input.form-control con !important; por eso aquí también. */
            input.form-control.dato-turno-destacado {
                color: #d40000 !important;
                font-size: 1.15rem;
                font-weight: 700;
            }
        </style>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->has('correccion'))
            {{-- La lectura final corregida ya es la inicial del turno siguiente: se confirma antes de guardar. --}}
            <div class="alert alert-warning">
                <strong>CORRECCIÓN DE LECTURAS.</strong> Esta corrección también cambia otro turno:
                <ul class="mb-2">
                    @foreach ($errors->get('correccion') as $aviso)
                        <li>{{ $aviso }}</li>
                    @endforeach
                </ul>
                <button type="submit" name="confirmar_correccion" value="1" class="btn btn-sm btn-danger">
                    CONFIRMAR CORRECCIÓN Y GUARDAR
                </button>
            </div>
        @endif

        @if ($errors->any() && ! $errors->has('correccion'))
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
            // Un turno guardado ya tiene su precio ligado (snapshot); uno nuevo usa el
            // precio que rige para su número de turno (no para la fecha: el precio puede
            // cambiar entre turnos del mismo día). Sin fallback a config(): si no hay un
            // registro de FuelPrice vigente, el precio es 0 para forzar a que se registre
            // el precio real en Precios de Combustible.
            $precioCorriente = isset($turno)
                ? $turno->precio_corriente
                : (float) \App\Models\FuelPrice::precioParaTurno('Gasolina', (int) $displayNumber);
            $precioAcpm = isset($turno)
                ? $turno->precio_acpm
                : (float) \App\Models\FuelPrice::precioParaTurno('ACPM', (int) $displayNumber);
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

        @if ($turnoRevisado)
            <div class="alert alert-danger mt-3 mb-0 text-center fs-4 fw-bold text-danger border-2 border-danger">
                <i class="bi bi-lock-fill"></i> Planilla REVISADA: el registro está bloqueado
            </div>
        @elseif ($turnoDeOtro)
            <div class="alert alert-warning mt-3 mb-0 text-center fw-bold">
                <i class="bi bi-lock-fill"></i> Esta planilla la registró otro usuario: solo la puede consultar.
                Si necesita una corrección, solicítela al jefe de patios o al administrador.
            </div>
        @endif

    </form>

    @if ($turno->exists && ! $turno->revisado && auth()->user()->isAdministrador() && $turno->surtidores->isNotEmpty())
        {{-- Cambio o reinicio del contador de una manguera: solo lo autoriza el administrador o el gerente. --}}
        <details class="pastel-section mt-3 d-print-none" @if ($errors->hasAny(['manguera', 'contador_retiro', 'contador_instalacion', 'observacion', 'contador'])) open @endif>
            <summary class="fw-bold">CAMBIO DE CONTADOR (solo administrador)</summary>
            <form method="POST" action="{{ route('turnos.cambio-contador', $turno) }}" class="mt-2"
                onsubmit="return confirm('¿Registrar el cambio de contador?');">
                @csrf
                <p class="small text-muted mb-2">Si se cambió o reinició el contador durante el turno: lectura del contador
                    viejo al retirarlo y del nuevo al instalarlo. La lectura final es la del contador nuevo. Galones =
                    (retiro − inicial) + (final − instalación). Con las dos lecturas en blanco se quita el cambio.</p>
                <div class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label small mb-0" for="contador-manguera">Manguera</label>
                        <select name="manguera" id="contador-manguera" class="form-select form-select-sm" required>
                            @foreach ($turno->surtidores as $surtidor)
                                <option value="{{ $surtidor->manguera }}" @selected(old('manguera') === $surtidor->manguera)>{{ $surtidor->manguera }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-0" for="contador-retiro">Lectura al retirar</label>
                        <input type="text" name="contador_retiro" id="contador-retiro" class="form-control form-control-sm"
                            inputmode="decimal" placeholder="1,250.000" value="{{ old('contador_retiro') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-0" for="contador-instalacion">Lectura al instalar</label>
                        <input type="text" name="contador_instalacion" id="contador-instalacion" class="form-control form-control-sm"
                            inputmode="decimal" placeholder="0.000" value="{{ old('contador_instalacion') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small mb-0" for="contador-observacion">Observación</label>
                        <input type="text" name="observacion" id="contador-observacion" class="form-control form-control-sm"
                            maxlength="500" required value="{{ old('observacion') }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-danger w-100">Registrar cambio</button>
                    </div>
                </div>
            </form>
        </details>
    @endif

    @if ($correcciones->isNotEmpty())
        <div class="pastel-section mt-3">
            <h2 class="h6 fw-bold">CORRECCIONES DE LECTURAS Y CAMBIOS DE CONTADOR</h2>
            <ul class="small mb-0">
                @foreach ($correcciones as $correccion)
                    <li>
                        {{ $correccion->created_at->format('d/m/Y H:i') }} — {{ $correccion->user?->name }}:
                        @if ($correccion->tipo === \App\Models\LecturaCorreccion::TIPO_CAMBIO_CONTADOR)
                            cambio de contador de {{ $correccion->manguera }} en el turno #{{ $correccion->turno?->numero_turno }}
                            @if ($correccion->contador_retiro !== null)
                                (retiro {{ number_format($correccion->contador_retiro, 3, '.', ',') }}, instalación
                                {{ number_format($correccion->contador_instalacion, 3, '.', ',') }})
                            @else
                                (se quitó)
                            @endif
                            — {{ $correccion->observacion }}
                        @else
                            lectura final de {{ $correccion->manguera }} del turno #{{ $correccion->turno?->numero_turno }}
                            {{ $correccion->valor_anterior === null ? 'en blanco' : number_format($correccion->valor_anterior, 3, '.', ',') }}
                            → {{ number_format($correccion->valor_nuevo, 3, '.', ',') }}; también cambió la inicial del turno
                            #{{ $correccion->turnoSiguiente?->numero_turno }}.
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Formulario independiente (no anidado) para marcar el turno como revisado --}}
    @if (isset($turno) && $turno && $turno->exists && !$turno->revisado && auth()->user()->isAdministrador())
        <form method="POST" action="{{ route('turnos.revisar', $turno) }}" id="form-marcar-revisado" class="d-none">
            @csrf
        </form>
    @endif

    @endif
@endsection
