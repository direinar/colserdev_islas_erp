@extends('layouts.app')

@section('content')

    @php
        // Una planilla revisada queda bloqueada para todos; el administrador puede
        // devolverla a PENDIENTE DE REVISIÓN para corregirla.
        $turnoRevisado = isset($turno) && $turno && $turno->revisado;
        // El islero y el jefe de patios solo modifican las planillas que ellos registraron.
        $turnoDeOtro = isset($turno) && $turno && ! $turnoRevisado && ! $turno->editablePor(auth()->user());
        $puedeGuardar = ! $turnoRevisado && ! $turnoDeOtro;
    @endphp

    <x-page-header title="Planilla de turnos" icon="journal-check"
        subtitle="Ventas, lecturas, formas de pago y cuadre de cada turno.">
        <a href="{{ route('turnos.create') }}" class="btn btn-outline-light btn-sm">
            <i class="bi bi-file-earmark-plus" aria-hidden="true"></i> Nuevo
        </a>
        @if ($puedeGuardar)
            <button type="button" class="btn btn-outline-light btn-sm" onclick="limpiarPlanilla()">
                <i class="bi bi-eraser" aria-hidden="true"></i> Limpiar
            </button>
            <button type="submit" form="turno-form" class="btn btn-light btn-sm">
                <i class="bi bi-save" aria-hidden="true"></i> Guardar
            </button>
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
            {
                // El conteo toma el valor de cada consignación por su No.: es obligatorio.
                campos: '.consignacion-valor-input',
                requisito: '.consignacion-no-input',
                titulo: 'Digite el No. de consignación',
                texto: 'En CONSIGNACIONES primero debe digitar el No. de la consignación y luego el valor. El conteo toma el valor de cada consignación por su número.',
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

    {{-- data-*: el borrador del navegador se borra solo cuando el guardado fue exitoso y no se
         ofrece recuperar en una planilla bloqueada; data-estado-url consulta, antes de guardar,
         si otro usuario la marcó como revisada mientras estaba abierta. --}}
    <form method="POST" action="{{ route('turnos.store') }}" id="turno-form"
        @if (session('planilla_guardada')) data-guardado-ok="1" @endif
        @if (session()->hasOldInput('numero_turno')) data-borrador-servidor="1" @endif
        @if (! $puedeGuardar) data-bloqueada="1" @endif
        @if (isset($turno) && $turno && $turno->exists && $puedeGuardar) data-estado-url="{{ route('turnos.estado', $turno) }}" @endif
        @if (session('borrador_descartar')) data-borrador-descartar="{{ session('borrador_descartar') }}" @endif>
        @csrf
        {{-- Planilla abierta con "Nuevo": si al guardar otro usuario ya tomó su número, se guarda
             con el siguiente libre en vez de sobrescribir o rechazar (TurnoController@store). --}}
        @unless (isset($turno) && $turno && $turno->exists)
            <input type="hidden" name="planilla_nueva" value="1">
        @endunless
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

        @if ($errors->any())
            <div class="alert alert-danger" id="errores-planilla">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>

            {{-- El aviso de arriba no se ve si se está más abajo en la planilla: se avisa también en
                 una ventana, para que no parezca que se guardó (lo digitado vuelve sin guardar). --}}
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: 'La planilla NO se guardó',
                        html: @json('<ul class="text-start mb-0">'.collect($errors->all())->map(fn ($error) => '<li>'.e($error).'</li>')->implode('').'</ul>'),
                        icon: 'error',
                        confirmButtonText: 'Corregir',
                    });
                });
            </script>
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

                @include('planillas.turnos.partials.precios')

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

    <script>
        // Si otro usuario marca la planilla como REVISADA mientras está abierta, se avisa antes de
        // guardar (y cada minuto) para no perder el trabajo: lo digitado queda en el borrador.
        (function() {
            const form = document.getElementById('turno-form');
            const url = form?.dataset.estadoUrl;
            if (!url) return;

            let verificada = false;

            const consultar = () => fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
                .then(respuesta => respuesta.ok ? respuesta.json() : null)
                .catch(() => null);

            const mensaje = estado => estado.revisado_por + ' marcó esta planilla como REVISADA' +
                (estado.revisado_at ? ' el ' + estado.revisado_at : '') +
                ' mientras usted la tenía abierta. Sus cambios no se pueden guardar, pero quedan en el borrador de este ' +
                'navegador: pida al administrador que la vuelva a Pendiente de revisión y al abrirla podrá recuperarlos.';

            const mostrarAviso = estado => {
                if (document.getElementById('aviso-revisada-mientras')) return;
                const aviso = document.createElement('div');
                aviso.id = 'aviso-revisada-mientras';
                aviso.className = 'alert alert-danger fw-bold';
                aviso.textContent = mensaje(estado);
                form.prepend(aviso);
            };

            form.addEventListener('submit', async function(event) {
                // Las validaciones de la pantalla ya bloquearon el envío, o ya se verificó.
                if (verificada || event.defaultPrevented) return;
                event.preventDefault();

                const estado = await consultar();
                if (estado?.revisado) {
                    mostrarAviso(estado);
                    Swal.fire({ title: 'Planilla REVISADA', text: mensaje(estado), icon: 'warning', confirmButtonText: 'Entendido' });
                    return;
                }

                // Sin conexión con el servidor se envía igual: el servidor también lo verifica.
                verificada = true;
                form.submit();
            });

            setInterval(async function() {
                const estado = await consultar();
                if (estado?.revisado) mostrarAviso(estado);
            }, 60000);
        })();
    </script>

    {{-- Formulario independiente (no anidado) para marcar el turno como revisado --}}
    @if (isset($turno) && $turno && $turno->exists && !$turno->revisado && auth()->user()->isAdministrador())
        <form method="POST" action="{{ route('turnos.revisar', $turno) }}" id="form-marcar-revisado" class="d-none">
            @csrf
        </form>
    @endif
@endsection
