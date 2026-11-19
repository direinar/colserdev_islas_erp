@extends('layouts.app')

@section('title', 'Fallas y eventos de la aplicación')

@php
    use App\Models\EventoAplicacion;
    use Illuminate\Support\Carbon;

    $filtrosActuales = array_filter($filtros + ['vista' => $vista], fn ($valor): bool => $valor !== null && $valor !== '');
    $conFiltro = fn (array $cambios): string => route('control-planillas.eventos.index',
        array_filter(array_merge(['desde' => $desde, 'hasta' => $hasta], $filtrosActuales, $cambios), fn ($valor): bool => $valor !== null));

    $iconosTipo = [
        EventoAplicacion::TIPO_ERROR => 'bug',
        EventoAplicacion::TIPO_ACCION => 'person-gear',
        EventoAplicacion::TIPO_ACCESO => 'box-arrow-in-right',
    ];
    $iconosSemaforo = ['rojo' => 'x-octagon-fill', 'amarillo' => 'exclamation-triangle-fill', 'verde' => 'check-circle-fill', 'gris' => 'dash-circle-fill'];
    $estadoTexto = EventoAplicacion::ESTADOS_GESTION + [EventoAplicacion::ESTADO_REGISTRADO => 'Registrado'];
    $estadoSemaforo = [
        EventoAplicacion::ESTADO_PENDIENTE => 'rojo',
        EventoAplicacion::ESTADO_EN_REVISION => 'amarillo',
        EventoAplicacion::ESTADO_RESUELTO => 'verde',
        EventoAplicacion::ESTADO_DESCARTADO => 'gris',
    ];
    $estadoGeneral = $semaforo['rojo'] > 0 ? 'rojo' : ($semaforo['amarillo'] > 0 ? 'amarillo' : 'verde');
@endphp

@section('content')
    <style>
        .ev-general { display: flex; align-items: center; gap: 1rem; padding: .9rem 1.1rem; border-radius: .75rem;
            background: var(--sem-suave); border: 1px solid var(--sem); margin-bottom: 1rem; }
        .ev-general .bi { font-size: 1.8rem; color: var(--sem); }
        .ev-general strong { color: var(--sem); }

        .ev-tarjeta { display: flex; gap: .85rem; align-items: center; height: 100%; padding: .9rem 1rem; border-radius: .75rem;
            background: #fff; border: 1px solid var(--bs-border-color); border-top: 4px solid var(--sem);
            color: inherit; text-decoration: none; transition: transform .12s ease, box-shadow .12s ease; }
        .ev-tarjeta:hover { transform: translateY(-2px); box-shadow: 0 .4rem 1rem rgba(0, 0, 0, .08); color: inherit; }
        .ev-tarjeta.activa { box-shadow: 0 0 0 3px var(--sem-suave), 0 0 0 4px var(--sem); }
        .ev-tarjeta .ev-icono { width: 2.75rem; height: 2.75rem; border-radius: 50%; display: grid; place-items: center;
            background: var(--sem-suave); color: var(--sem); font-size: 1.35rem; flex-shrink: 0; }
        .ev-tarjeta .ev-valor { font-size: 1.6rem; font-weight: 800; line-height: 1; }
        .ev-tarjeta .ev-etiqueta { font-weight: 600; font-size: .85rem; }
        .ev-tarjeta .ev-ayuda { font-size: .74rem; color: #6c757d; }

        .ev-vistas .btn { border-radius: 999px; }

        .ev-tabla td { vertical-align: middle; }
        .ev-tabla tr.ev-fila { border-left: 4px solid var(--sem); }
        .ev-tabla .ev-titulo { font-weight: 600; }
        .ev-tabla .ev-meta { font-size: .75rem; color: #6c757d; }
        .ev-tabla tr.ev-detalle > td { background: #f8f9fa; }
        .ev-traza { max-height: 220px; overflow: auto; font-size: .72rem; background: #212529; color: #e9ecef; padding: .6rem; border-radius: .4rem; }
        .ev-linea-tiempo { list-style: none; padding-left: 0; margin: 0; }
        .ev-linea-tiempo li { position: relative; padding: 0 0 .5rem 1.2rem; font-size: .82rem; }
        .ev-linea-tiempo li::before { content: ''; position: absolute; left: .25rem; top: .35rem; width: .5rem; height: .5rem; border-radius: 50%; background: var(--sem, #6c757d); }

        .ev-lote { position: sticky; bottom: .75rem; z-index: 20; }

        .ev-opciones-estado .btn-check + label { border-radius: 999px; border: 1px solid var(--sem); color: var(--sem); background: #fff; font-weight: 600; }
        .ev-opciones-estado .btn-check:checked + label { background: var(--sem); color: #fff; }
    </style>

    <x-page-header title="Auditoría del administrador" icon="shield-exclamation"
        subtitle="FALLAS Y EVENTOS DE LA APLICACIÓN. Errores del sistema, acciones de los usuarios e ingresos, con su gestión por semáforo.">
        @include('informes.partials.filtro-rango', ['ruta' => 'control-planillas.eventos.index', 'filtrosExtra' => $filtrosActuales])
    </x-page-header>

    @include('control-planillas.partials.pestanas', ['activa' => 'eventos'])

    @if (session('success'))
        <div class="alert alert-success d-print-none">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-print-none">{{ $errors->first() }}</div>
    @endif

    {{-- Estado general --}}
    <div class="ev-general semaforo-{{ $estadoGeneral }}" id="estado-general">
        <i class="bi bi-{{ $iconosSemaforo[$estadoGeneral] }}" aria-hidden="true"></i>
        <div>
            @if ($estadoGeneral === 'rojo')
                <strong>Hay {{ $semaforo['rojo'] }} falla(s) crítica(s) sin atender.</strong>
                Ábralas, revise el detalle y gestiónelas: márquelas en revisión mientras se corrigen y como resueltas al terminar.
            @elseif ($estadoGeneral === 'amarillo')
                <strong>Sin fallas críticas.</strong> Quedan {{ $semaforo['amarillo'] }} advertencia(s) o casos en revisión por cerrar.
            @else
                <strong>Todo en orden.</strong> No hay fallas ni advertencias pendientes de gestión.
            @endif
        </div>
    </div>

    {{-- Semáforo --}}
    <div class="row g-2 mb-3">
        @foreach (EventoAplicacion::SEMAFOROS as $color => [$etiqueta, $ayuda])
            <div class="col-6 col-lg-3">
                <a href="{{ $conFiltro(['vista' => $color, 'page' => null]) }}" @class(['ev-tarjeta', 'semaforo-'.$color, 'activa' => $vista === $color])
                    data-semaforo="{{ $color }}">
                    <span class="ev-icono"><i class="bi bi-{{ $iconosSemaforo[$color] }}" aria-hidden="true"></i></span>
                    <span>
                        <span class="ev-valor d-block">{{ $semaforo[$color] }}</span>
                        <span class="ev-etiqueta d-block">{{ $etiqueta }}</span>
                        <span class="ev-ayuda d-block">{{ $ayuda }}{{ in_array($color, ['verde', 'gris'], true) ? ' (en el periodo)' : '' }}</span>
                    </span>
                </a>
            </div>
        @endforeach
    </div>

    {{-- Filtros --}}
    <div class="card mb-3 d-print-none">
        <div class="card-body py-2">
            <div class="d-flex flex-wrap gap-2 align-items-center mb-2 ev-vistas">
                <span class="small text-muted me-1">VER:</span>
                <a href="{{ $conFiltro(['vista' => 'por_gestionar', 'page' => null]) }}" @class(['btn btn-sm', 'btn-primary' => $vista === 'por_gestionar', 'btn-outline-primary' => $vista !== 'por_gestionar'])>
                    <i class="bi bi-inbox" aria-hidden="true"></i> Por gestionar <span class="badge text-bg-light">{{ $porGestionar }}</span>
                </a>
                <a href="{{ $conFiltro(['vista' => 'todos', 'page' => null]) }}" @class(['btn btn-sm', 'btn-primary' => $vista === 'todos', 'btn-outline-primary' => $vista !== 'todos'])>
                    <i class="bi bi-list-ul" aria-hidden="true"></i> Todo el historial del periodo
                </a>
            </div>
            <form method="GET" action="{{ route('control-planillas.eventos.index') }}" class="row g-2 align-items-end">
                <input type="hidden" name="desde" value="{{ $desde }}">
                <input type="hidden" name="hasta" value="{{ $hasta }}">
                <input type="hidden" name="vista" value="{{ $vista }}">
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-0" for="tipo">TIPO</label>
                    <select name="tipo" id="tipo" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Todos</option>
                        @foreach (EventoAplicacion::TIPOS as $clave => $nombre)
                            <option value="{{ $clave }}" @selected(($filtros['tipo'] ?? '') === $clave)>{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-0" for="modulo">MÓDULO</label>
                    <select name="modulo" id="modulo" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Todos</option>
                        @foreach ($modulos as $modulo)
                            <option value="{{ $modulo }}" @selected(($filtros['modulo'] ?? '') === $modulo)>{{ $modulo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-0" for="usuario">USUARIO</label>
                    <select name="usuario" id="usuario" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Todos</option>
                        @foreach ($usuarios as $usuario)
                            <option value="{{ $usuario->id }}" @selected((int) ($filtros['usuario'] ?? 0) === $usuario->id)>{{ $usuario->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-0" for="buscar">BUSCAR</label>
                    <input type="search" name="buscar" id="buscar" class="form-control form-control-sm" value="{{ $filtros['buscar'] ?? '' }}"
                        placeholder="Texto, planilla, pantalla…">
                </div>
                <div class="col-12 col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary flex-fill"><i class="bi bi-funnel" aria-hidden="true"></i> Filtrar</button>
                    <a href="{{ route('control-planillas.eventos.index', ['desde' => $desde, 'hasta' => $hasta]) }}" class="btn btn-sm btn-outline-secondary" title="Quitar filtros">
                        <i class="bi bi-x-lg" aria-hidden="true"></i><span class="visually-hidden">Quitar filtros</span>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Listado --}}
    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            @php
                $tituloListado = match ($vista) {
                    'por_gestionar' => 'FALLAS Y ADVERTENCIAS POR GESTIONAR',
                    'todos' => 'HISTORIAL DE EVENTOS DEL '.Carbon::parse($desde)->format('d/m/Y').' AL '.Carbon::parse($hasta)->format('d/m/Y'),
                    default => mb_strtoupper(EventoAplicacion::SEMAFOROS[$vista][0]),
                };
            @endphp
            <span class="fw-bold">{{ $tituloListado }} ({{ $eventos->total() }})</span>
            <span class="small text-muted d-print-none">
                <span class="semaforo-punto semaforo-rojo"></span> Crítico sin atender
                <span class="semaforo-punto semaforo-amarillo ms-2"></span> Advertencia / en revisión
                <span class="semaforo-punto semaforo-verde ms-2"></span> Resuelto / sin novedad
                <span class="semaforo-punto semaforo-gris ms-2"></span> Descartado
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0 ev-tabla" id="tabla-eventos">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center d-print-none" style="width:2rem;">
                                <input type="checkbox" class="form-check-input" id="marcar-todos" aria-label="Marcar todos">
                            </th>
                            <th>EVENTO</th>
                            <th>USUARIO</th>
                            <th>CUÁNDO</th>
                            <th class="text-center">ESTADO</th>
                            <th class="text-end d-print-none">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($eventos as $evento)
                            @php($color = $evento->semaforo())
                            <tr class="ev-fila semaforo-{{ $color }}" data-evento="{{ $evento->id }}">
                                <td class="text-center d-print-none">
                                    @if ($evento->porGestionar())
                                        <input type="checkbox" class="form-check-input marcar-evento" name="eventos[]" value="{{ $evento->id }}" form="form-lote"
                                            aria-label="Marcar evento {{ $evento->id }}">
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-2 align-items-start">
                                        <span class="semaforo-punto semaforo-{{ $color }} mt-1" title="{{ ucfirst($color) }}"></span>
                                        <div>
                                            <div class="ev-titulo">{{ $evento->titulo }}</div>
                                            <div class="ev-meta">
                                                <i class="bi bi-{{ $iconosTipo[$evento->tipo] ?? 'circle' }}" aria-hidden="true"></i>
                                                {{ EventoAplicacion::TIPOS[$evento->tipo] ?? $evento->tipo }}
                                                · {{ EventoAplicacion::NIVELES[$evento->nivel] ?? $evento->nivel }}
                                                @if ($evento->modulo)
                                                    · <span class="badge text-bg-light border">{{ $evento->modulo }}</span>
                                                @endif
                                                @if ($evento->detalle['reincidente'] ?? false)
                                                    · <span class="badge text-bg-danger">REINCIDENTE</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="small">{{ $evento->user?->name ?? '—' }}</td>
                                <td class="small text-nowrap">
                                    {{ $evento->ultima_vez?->format('d/m/Y H:i') }}
                                    @if ($evento->ocurrencias > 1)
                                        <div><span class="badge text-bg-dark" title="Veces que ocurrió">× {{ $evento->ocurrencias }} veces</span></div>
                                        <div class="ev-meta">desde {{ $evento->created_at->format('d/m/Y H:i') }}</div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="semaforo-insignia semaforo-{{ $color }}">{{ $estadoTexto[$evento->estado] ?? $evento->estado }}</span>
                                    @if ($evento->gestor)
                                        <div class="ev-meta mt-1">{{ $evento->gestor->name }} · {{ $evento->gestionado_at?->format('d/m H:i') }}</div>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap d-print-none">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse"
                                        data-bs-target="#detalle-{{ $evento->id }}" aria-expanded="false" title="Ver detalle">
                                        <i class="bi bi-eye" aria-hidden="true"></i><span class="d-none d-xl-inline"> Detalle</span>
                                    </button>
                                    @if ($evento->estado === EventoAplicacion::ESTADO_PENDIENTE)
                                        <form method="POST" action="{{ route('control-planillas.eventos.gestionar', $evento) }}" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="estado" value="{{ EventoAplicacion::ESTADO_EN_REVISION }}">
                                            <button type="submit" class="btn btn-sm btn-outline-warning" title="Tomar en revisión">
                                                <i class="bi bi-hourglass-split" aria-hidden="true"></i><span class="d-none d-xl-inline"> En revisión</span>
                                            </button>
                                        </form>
                                    @endif
                                    @if ($evento->estado !== EventoAplicacion::ESTADO_REGISTRADO)
                                        <button type="button" class="btn btn-sm btn-success btn-gestionar" data-bs-toggle="modal" data-bs-target="#modal-gestion"
                                            data-url="{{ route('control-planillas.eventos.gestionar', $evento) }}"
                                            data-titulo="{{ $evento->titulo }}"
                                            data-estado="{{ $evento->porGestionar() ? EventoAplicacion::ESTADO_RESUELTO : $evento->estado }}"
                                            data-observacion="{{ $evento->observacion }}">
                                            <i class="bi bi-check2-circle" aria-hidden="true"></i><span class="d-none d-xl-inline"> Gestionar</span>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                            <tr class="collapse ev-detalle" id="detalle-{{ $evento->id }}">
                                <td></td>
                                <td colspan="5">
                                    <div class="row g-3 py-2">
                                        <div class="col-lg-7">
                                            @if ($evento->descripcion)
                                                <div class="mb-2"><strong>Descripción:</strong> {{ $evento->descripcion }}</div>
                                            @endif
                                            <dl class="row small mb-2">
                                                @if ($evento->url)
                                                    <dt class="col-sm-3">Pantalla</dt>
                                                    <dd class="col-sm-9 text-break">{{ $evento->metodo }} {{ $evento->url }}</dd>
                                                @endif
                                                @if ($evento->estado_http)
                                                    <dt class="col-sm-3">Respuesta</dt>
                                                    <dd class="col-sm-9">HTTP {{ $evento->estado_http }}</dd>
                                                @endif
                                                @if ($evento->ip)
                                                    <dt class="col-sm-3">IP</dt>
                                                    <dd class="col-sm-9">{{ $evento->ip }}</dd>
                                                @endif
                                                @if ($evento->detalle['origen'] ?? null)
                                                    <dt class="col-sm-3">Origen</dt>
                                                    <dd class="col-sm-9"><code>{{ $evento->detalle['origen'] }}</code></dd>
                                                @endif
                                                @if ($evento->detalle['clase'] ?? null)
                                                    <dt class="col-sm-3">Tipo de error</dt>
                                                    <dd class="col-sm-9"><code>{{ $evento->detalle['clase'] }}</code></dd>
                                                @endif
                                            </dl>
                                            @if ($evento->detalle['traza'] ?? [])
                                                <details>
                                                    <summary class="small">Traza técnica (para soporte)</summary>
                                                    <pre class="ev-traza mt-1 mb-0">{{ implode("\n", $evento->detalle['traza']) }}</pre>
                                                </details>
                                            @endif
                                        </div>
                                        <div class="col-lg-5">
                                            <div class="small fw-bold mb-1">HISTORIAL DE GESTIÓN</div>
                                            @if ($evento->detalle['gestiones'] ?? [])
                                                <ul class="ev-linea-tiempo">
                                                    @foreach ($evento->detalle['gestiones'] as $gestion)
                                                        <li class="semaforo-{{ $estadoSemaforo[$gestion['estado']] ?? 'gris' }}">
                                                            <strong>{{ $estadoTexto[$gestion['estado']] ?? $gestion['estado'] }}</strong>
                                                            — {{ $gestion['usuario'] }}, {{ Carbon::parse($gestion['fecha'])->format('d/m/Y H:i') }}
                                                            @if ($gestion['observacion'])
                                                                <div class="text-muted">{{ $gestion['observacion'] }}</div>
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <p class="small text-muted mb-0">{{ $evento->porGestionar() ? 'Aún no se ha gestionado.' : 'Evento informativo: no requiere gestión.' }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="bi bi-check-circle-fill text-success fs-3 d-block" aria-hidden="true"></i>
                                    <span class="text-muted">{{ $vista === 'por_gestionar' ? 'No hay fallas ni advertencias por gestionar.' : 'No hay eventos con estos filtros.' }}</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($eventos->hasPages())
            <div class="card-footer d-print-none">{{ $eventos->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>

    {{-- Gestión en lote: aparece al marcar eventos. --}}
    <form method="POST" action="{{ route('control-planillas.eventos.gestionar-varios') }}" id="form-lote" class="ev-lote d-print-none" data-sin-aviso hidden>
        @csrf
        @method('PATCH')
        <div class="card shadow border-primary mt-3">
            <div class="card-body py-2 d-flex flex-wrap gap-2 align-items-center">
                <strong><span id="lote-cantidad">0</span> marcado(s)</strong>
                <select name="estado" class="form-select form-select-sm w-auto" aria-label="Nuevo estado">
                    <option value="{{ EventoAplicacion::ESTADO_RESUELTO }}">Resuelto</option>
                    <option value="{{ EventoAplicacion::ESTADO_EN_REVISION }}">En revisión</option>
                    <option value="{{ EventoAplicacion::ESTADO_DESCARTADO }}">Descartado</option>
                </select>
                <input type="text" name="observacion" class="form-control form-control-sm flex-fill" style="min-width: 14rem;" required maxlength="1000"
                    placeholder="Observación: qué se hizo o por qué se descarta">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-check2-all" aria-hidden="true"></i> Aplicar</button>
            </div>
        </div>
    </form>

    {{-- Modal de gestión --}}
    <div class="modal fade" id="modal-gestion" tabindex="-1" aria-labelledby="modal-gestion-titulo" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content" id="form-gestion" data-sin-aviso>
                @csrf
                @method('PATCH')
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="modal-gestion-titulo"><i class="bi bi-clipboard-check" aria-hidden="true"></i> Gestionar evento</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-2" id="gestion-evento"></p>
                    <div class="mb-3">
                        <div class="form-label fw-semibold">Nuevo estado</div>
                        <div class="d-flex flex-wrap gap-2 ev-opciones-estado">
                            @foreach (EventoAplicacion::ESTADOS_GESTION as $clave => $nombre)
                                <span class="semaforo-{{ $estadoSemaforo[$clave] }}">
                                    <input type="radio" class="btn-check" name="estado" id="estado-{{ $clave }}" value="{{ $clave }}" autocomplete="off" required>
                                    <label class="btn btn-sm px-3" for="estado-{{ $clave }}">{{ $nombre }}</label>
                                </span>
                            @endforeach
                        </div>
                    </div>
                    <label class="form-label fw-semibold" for="gestion-observacion">Observación</label>
                    <textarea name="observacion" id="gestion-observacion" class="form-control" rows="3" maxlength="1000"
                        placeholder="Qué se revisó, qué se corrigió o por qué se descarta."></textarea>
                    <div class="form-text">Obligatoria para cerrar el evento como resuelto o descartado.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save" aria-hidden="true"></i> Guardar gestión</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Modal: carga el evento elegido.
            const formGestion = document.getElementById('form-gestion');
            const observacion = document.getElementById('gestion-observacion');

            function exigirObservacion() {
                const estado = formGestion.querySelector('input[name="estado"]:checked')?.value;
                observacion.required = estado === 'resuelto' || estado === 'descartado';
            }

            document.querySelectorAll('.btn-gestionar').forEach((boton) => boton.addEventListener('click', function () {
                formGestion.action = boton.dataset.url;
                document.getElementById('gestion-evento').textContent = boton.dataset.titulo;
                observacion.value = boton.dataset.observacion || '';
                const opcion = document.getElementById('estado-' + boton.dataset.estado);
                if (opcion) {
                    opcion.checked = true;
                }
                exigirObservacion();
            }));
            formGestion.querySelectorAll('input[name="estado"]').forEach((opcion) => opcion.addEventListener('change', exigirObservacion));
            document.getElementById('modal-gestion').addEventListener('shown.bs.modal', () => observacion.focus());

            // Gestión en lote.
            const formLote = document.getElementById('form-lote');
            const casillas = document.querySelectorAll('.marcar-evento');
            const todos = document.getElementById('marcar-todos');

            function actualizarLote() {
                const marcadas = Array.from(casillas).filter((casilla) => casilla.checked).length;
                document.getElementById('lote-cantidad').textContent = marcadas;
                formLote.hidden = marcadas === 0;
                todos.checked = marcadas > 0 && marcadas === casillas.length;
            }

            casillas.forEach((casilla) => casilla.addEventListener('change', actualizarLote));
            todos.addEventListener('change', function () {
                casillas.forEach((casilla) => casilla.checked = todos.checked);
                actualizarLote();
            });
            todos.disabled = casillas.length === 0;
        });
    </script>
@endsection
