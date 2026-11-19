@extends('layouts.app')

@section('title', 'Auditoría del administrador')

@php
    use App\Services\LecturaElectronicaService;
    use Illuminate\Support\Carbon;

    $pesos = fn (?float $valor): string => $valor === null ? '' : '$'.number_format($valor, 0, ',', '.');
    $galones = fn (?float $valor): string => $valor === null ? '' : number_format($valor, 2, ',', '.');
    $rango = ['desde' => $desde, 'hasta' => $hasta];

    $estados = [
        'pendiente' => ['PENDIENTE DE REVISIÓN', 'text-bg-warning'],
        'segunda_revision' => ['2ª REVISIÓN', 'text-bg-danger'],
        'anulada' => ['ANULADA', 'text-bg-secondary'],
        'revisada' => ['REVISADA', 'text-bg-success'],
    ];

    $conDiferenciaLectura = collect($lecturas['filas'])
        ->filter(fn (array $fila): bool => $fila['diferencia'] !== null && abs($fila['diferencia']) > LecturaElectronicaService::UMBRAL_ALERTA)
        ->count();
    $conDiferenciaInventario = collect($inventarios)
        ->filter(fn (array $fila): bool => round(abs($fila['corriente']['diferencia'] ?? 0), 2) != 0.0 || round(abs($fila['acpm']['diferencia'] ?? 0), 2) != 0.0)
        ->count();
    $descuadrados = collect($comprobantes)->filter(fn (array $comprobante): bool => $comprobante['diferencia'] != 0.0)->count();

    $resumen = [
        ['#alertas', 'Alertas sin atender', $alertas->count()],
        ['#control-planillas', 'Pendientes de revisión', $revision['conteos']['pendiente']],
        ['#control-planillas', '2ª revisión', $revision['conteos']['segunda_revision']],
        ['#incompletas', 'Planillas incompletas', $incompletas->count()],
        ['#lecturas', 'Días con diferencia en lecturas', $conDiferenciaLectura],
        ['#inventarios', 'Días con diferencia en inventario', $conDiferenciaInventario],
        ['#comprobantes', 'Comprobantes descuadrados', $descuadrados],
    ];
@endphp

@section('content')
    @include('informes.partials.impresion')

    <style>
        .auditoria-resumen a {
            display: block;
            height: 100%;
            padding: .5rem .75rem;
            border: 1px solid var(--bs-border-color);
            border-left-width: 4px;
            border-radius: .375rem;
            background: #fff;
            color: inherit;
            text-decoration: none;
        }

        .auditoria-resumen a.con-novedad { border-left-color: #dc3545; }
        .auditoria-resumen a.sin-novedad { border-left-color: #198754; }
        .auditoria-resumen .valor { font-size: 1.35rem; font-weight: 700; line-height: 1.1; }
        .auditoria-resumen a.con-novedad .valor { color: #dc3545; }
        .auditoria-resumen .etiqueta { font-size: .78rem; color: #6c757d; }

        .filtro-estado.activo { outline: 3px solid rgba(13, 110, 253, .45); }
        .auditoria-tabla td.num, .auditoria-tabla th.num { text-align: right; white-space: nowrap; }
        .auditoria-tabla .diferencia { color: #dc3545; font-weight: 700; }

        @media print {
            .card { break-inside: avoid; }
        }
    </style>

    <x-page-header title="Auditoría del administrador" icon="shield-exclamation"
        :subtitle="'INFORME DE AUDITORÍA del '.Carbon::parse($desde)->format('d/m/Y').' al '.Carbon::parse($hasta)->format('d/m/Y').'. Alertas, estado de revisión, planillas incompletas, lecturas, inventarios y comprobantes.'">
        @include('informes.partials.filtro-rango', ['ruta' => 'control-planillas.index'])
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success d-print-none">{{ session('success') }}</div>
    @endif

    {{-- Resumen: cada recuadro lleva a su bloque. --}}
    <div class="row g-2 mb-3 auditoria-resumen">
        @foreach ($resumen as [$ancla, $etiqueta, $valor])
            <div class="col-6 col-md-3 col-xl">
                <a href="{{ $ancla }}" class="{{ $valor > 0 ? 'con-novedad' : 'sin-novedad' }}">
                    <div class="valor">{{ $valor }}</div>
                    <div class="etiqueta">{{ $etiqueta }}</div>
                </a>
            </div>
        @endforeach
    </div>

    {{-- CONTROL PLANILLAS --}}
    <div class="card mb-3" id="control-planillas">
        <div class="card-header fw-bold">CONTROL PLANILLAS — ESTADO DE REVISIÓN DEL PERIODO ({{ $revision['planillas']->count() }})</div>
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 mb-2 d-print-none">
                @foreach ($estados as $clave => [$nombre, $clase])
                    <button type="button" class="btn btn-sm badge {{ $clase }} filtro-estado {{ $clave !== 'revisada' ? 'activo' : '' }}"
                        data-estado="{{ $clave }}" style="font-size:.85rem;">
                        {{ $nombre }}: {{ $revision['conteos'][$clave] }}
                    </button>
                @endforeach
            </div>
            <p class="small text-muted d-print-none">Toque un estado para mostrarlo u ocultarlo. Una planilla revisada que se
                devuelve a pendiente para corregirla queda en <strong>2ª REVISIÓN</strong> hasta que se vuelva a revisar.</p>
            <div class="table-responsive titulos-fijos">
                <table class="table table-bordered table-sm mb-0" id="tabla-revision">
                    <thead class="table-light">
                        <tr>
                            <th>TURNO</th>
                            <th>FECHA</th>
                            <th>REGISTRÓ</th>
                            <th class="text-center">ESTADO</th>
                            <th>DETALLE</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($revision['planillas'] as $planilla)
                            <tr data-estado="{{ $planilla['estado'] }}" @if ($planilla['estado'] === 'revisada') hidden @endif>
                                <td><a href="{{ route('turnos.create', ['turno_busqueda' => $planilla['turno']->numero_turno]) }}">#{{ $planilla['turno']->numero_turno }}</a></td>
                                <td>{{ $planilla['turno']->fecha->format('d/m/Y') }}</td>
                                <td>{{ $planilla['turno']->user?->name ?? $planilla['turno']->nombre_vendedor }}</td>
                                <td class="text-center"><span class="badge {{ $estados[$planilla['estado']][1] }}">{{ $estados[$planilla['estado']][0] }}</span></td>
                                <td class="small">{{ $planilla['detalle'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No hay planillas en el periodo.</td>
                            </tr>
                        @endforelse
                        <tr id="revision-sin-filas" hidden>
                            <td colspan="5" class="text-center text-muted">No hay planillas con los estados seleccionados.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ALERTAS --}}
    <div class="card mb-3" id="alertas">
        <div class="card-header fw-bold {{ $alertas->isNotEmpty() ? 'text-danger' : '' }}">
            ALERTAS: GUARDADOS QUE QUITARON DATOS ({{ $alertas->count() }})
        </div>
        <div class="card-body">
            <p class="small text-muted">Cada vez que se guarda una planilla se compara con el guardado anterior. Si quedó con
                menos filas en alguna sección o con menor total de ventas, aparece aquí con quién la guardó y cuándo. Revise la
                planilla y, si el cambio era correcto, márquela como revisada.</p>
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0">
                    <thead class="table-danger">
                        <tr>
                            <th>TURNO</th>
                            <th>FECHA Y HORA</th>
                            <th>GUARDÓ</th>
                            <th>QUÉ SE QUITÓ</th>
                            <th class="text-center">ESTADO</th>
                            <th class="text-center d-print-none">ACCIÓN</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($alertas as $alerta)
                            <tr>
                                <td class="text-nowrap">
                                    @if ($alerta->turno)
                                        <a href="{{ route('turnos.create', ['turno_busqueda' => $alerta->numero_turno]) }}">#{{ $alerta->numero_turno }}</a>
                                    @else
                                        #{{ $alerta->numero_turno }} <span class="badge text-bg-danger">ELIMINADA</span>
                                    @endif
                                    <div class="d-print-none"><a class="small" href="{{ route('control-planillas.historial', $alerta->numero_turno) }}">Historial</a></div>
                                </td>
                                <td class="text-nowrap">{{ $alerta->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $alerta->user?->name }}</td>
                                <td>{{ $alerta->alerta }}</td>
                                <td class="text-center"><span class="badge text-bg-danger">No atendida</span></td>
                                <td class="text-center d-print-none">
                                    <form method="POST" action="{{ route('control-planillas.atender', $alerta) }}" class="mb-0">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success">Revisada</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No hay alertas pendientes.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- PLANILLAS INCOMPLETAS Y NÚMEROS FALTANTES --}}
    <div class="row g-3 mb-3" id="incompletas">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header fw-bold">PLANILLAS INCOMPLETAS ({{ $incompletas->count() }})</div>
                <div class="card-body">
                    <div class="table-responsive titulos-fijos">
                        <table class="table table-bordered table-sm mb-0">
                            <thead class="table-warning">
                                <tr>
                                    <th>TURNO</th>
                                    <th>FECHA</th>
                                    <th>REGISTRÓ</th>
                                    <th>QUÉ FALTA</th>
                                    <th class="text-center d-print-none">ACCIÓN</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($incompletas as $fila)
                                    <tr>
                                        <td><a href="{{ route('turnos.create', ['turno_busqueda' => $fila['turno']->numero_turno]) }}">#{{ $fila['turno']->numero_turno }}</a></td>
                                        <td>{{ $fila['turno']->fecha->format('d/m/Y') }}</td>
                                        <td>{{ $fila['turno']->user?->name ?? $fila['turno']->nombre_vendedor }}</td>
                                        <td>{{ $fila['motivo'] }}</td>
                                        <td class="text-center d-print-none">
                                            @if ($fila['sin_diligenciar'])
                                                <form method="POST" action="{{ route('turnos.anular', $fila['turno']) }}" class="mb-0"
                                                    onsubmit="return confirm('¿Anular el turno #{{ $fila['turno']->numero_turno }}? Esta acción no se puede deshacer.')">
                                                    @csrf
                                                    <input type="hidden" name="motivo_anulacion" value="Planilla guardada sin diligenciar">
                                                    <input type="hidden" name="volver" value="auditoria">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Anular planilla</button>
                                                </form>
                                            @else
                                                <a href="{{ route('turnos.create', ['turno_busqueda' => $fila['turno']->numero_turno]) }}" class="btn btn-sm btn-outline-primary">Completar</a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No hay planillas incompletas en el periodo.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header fw-bold {{ $faltantes ? 'text-danger' : '' }}">NÚMEROS DE TURNO FALTANTES ({{ count($faltantes) }})</div>
                <div class="card-body">
                    @if ($faltantes)
                        <p class="small text-muted">Planillas que ya no están en el sistema (el consecutivo salta):</p>
                        <div class="d-flex flex-wrap gap-1">
                            @foreach ($faltantes as $numero)
                                <a href="{{ route('control-planillas.historial', $numero) }}" class="badge text-bg-danger text-decoration-none">#{{ $numero }}</a>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted mb-0">El consecutivo de turnos está completo.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        {{-- LECTURAS ELECTRÓNICAS --}}
        <div class="col-xl-5" id="lecturas">
            <div class="card h-100">
                <div class="card-header fw-bold">LECTURAS ELECTRÓNICAS</div>
                <div class="card-body">
                    <p class="small text-muted">Venta del día según lectura electrónica contra los cierres de IAPROPIADA. En rojo, las
                        diferencias mayores a {{ $pesos(LecturaElectronicaService::UMBRAL_ALERTA) }}.</p>
                    <div class="table-responsive titulos-fijos">
                        <table class="table table-bordered table-sm mb-0 auditoria-tabla">
                            <thead class="table-light">
                                <tr>
                                    <th>FECHA</th>
                                    <th class="num">LECT. ELECTRÓNICA</th>
                                    <th class="num">I/APROPIADA</th>
                                    <th class="num">DIFERENCIA</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($lecturas['filas'] as $fila)
                                    <tr>
                                        <td><a href="{{ route('lectura-electronica.index', ['fecha' => $fila['fecha']]) }}">{{ Carbon::parse($fila['fecha'])->format('d/m/Y') }}</a></td>
                                        @if ($fila['completa'])
                                            <td class="num">{{ $pesos($fila['lectura']) }}</td>
                                        @else
                                            <td class="num text-warning-emphasis">Sin lectura completa</td>
                                        @endif
                                        <td class="num">{{ $pesos($fila['iapropiada']) }}</td>
                                        <td @class(['num', 'diferencia' => $fila['diferencia'] !== null && abs($fila['diferencia']) > LecturaElectronicaService::UMBRAL_ALERTA])>
                                            {{ $pesos($fila['diferencia']) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Sin lecturas ni planillas en el periodo.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if ($lecturas['filas'])
                                <tfoot class="fw-bold">
                                    <tr>
                                        <td>TOTAL <span class="fw-normal small">(días con lectura completa)</span></td>
                                        <td class="num">{{ $pesos($lecturas['totales']['lectura']) }}</td>
                                        <td class="num">{{ $pesos($lecturas['totales']['iapropiada']) }}</td>
                                        <td class="num">{{ $pesos($lecturas['totales']['diferencia']) }}</td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ESTADO DE ÚLTIMAS PLANILLAS (antes en el dashboard) --}}
        <div class="col-xl-7" id="ultimas">
            <div class="card h-100">
                <div class="card-header fw-bold">ESTADO DE ÚLTIMAS PLANILLAS DEL PERIODO</div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>TURNO</th>
                                <th>FECHA</th>
                                <th>VENDEDOR</th>
                                <th class="text-end">VENTAS</th>
                                <th>CUADRE</th>
                                <th>REVISIÓN</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ultimas as $turno)
                                @php($pendiente = $turno->sobrante_pendiente === null ? null : (float) $turno->sobrante_pendiente)
                                <tr>
                                    <td><a href="{{ route('turnos.create', ['turno_busqueda' => $turno->numero_turno]) }}">#{{ $turno->numero_turno }}</a></td>
                                    <td>{{ $turno->fecha->format('d/m/Y') }}</td>
                                    <td>{{ $turno->nombre_vendedor }}</td>
                                    <td class="text-end">{{ $pesos((float) $turno->tirillas_valor_corriente + (float) $turno->tirillas_valor_acpm + (float) $turno->canastilla_total) }}</td>
                                    <td>
                                        @if ($pendiente === null)
                                            <span class="estado"><i class="bi bi-dash-circle estado-neutro" aria-hidden="true"></i> Sin calcular</span>
                                        @elseif (abs($pendiente) < 1)
                                            <span class="estado"><i class="bi bi-check-circle-fill estado-bien" aria-hidden="true"></i> Cuadrada</span>
                                        @else
                                            <span class="estado"><i class="bi bi-exclamation-triangle-fill estado-critico" aria-hidden="true"></i> {{ $pendiente < 0 ? 'Faltante' : 'Sobrante' }} {{ $pesos(abs($pendiente)) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($turno->revisado)
                                            <span class="estado"><i class="bi bi-patch-check-fill estado-bien" aria-hidden="true"></i> Revisada</span>
                                        @else
                                            <span class="estado"><i class="bi bi-hourglass-split estado-advertencia" aria-hidden="true"></i> Pendiente</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No hay planillas en el periodo.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        {{-- INVENTARIOS - UNIDADES --}}
        <div class="col-12" id="inventarios">
            <div class="card h-100">
                <div class="card-header fw-bold">INVENTARIOS — UNIDADES (GALONES)</div>
                <div class="card-body">
                    <p class="small text-muted">Saldo de cada día según el <a href="{{ route('control-tanques.index', $rango) }}">informe
                        de tanques</a> contra el saldo del inventario (kardex). En rojo, los días en que no coinciden.</p>
                    <div class="table-responsive titulos-fijos">
                        <table class="table table-bordered table-sm mb-0 auditoria-tabla">
                            <thead class="table-light">
                                <tr>
                                    <th rowspan="2" class="align-middle">FECHA</th>
                                    <th colspan="3" class="text-center">G A S O L I N A</th>
                                    <th colspan="3" class="text-center">A C P M</th>
                                </tr>
                                <tr>
                                    @foreach (['corriente', 'acpm'] as $combustible)
                                        <th class="num">INFORME TANQUES</th>
                                        <th class="num">INVENTARIO</th>
                                        <th class="num">DIFERENCIA</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($inventarios as $fila)
                                    <tr>
                                        <td class="text-nowrap">{{ Carbon::parse($fila['fecha'])->format('d/m/Y') }}</td>
                                        @foreach (['corriente', 'acpm'] as $combustible)
                                            @php($dato = $fila[$combustible])
                                            <td class="num">{{ $galones($dato['tanques']) }}</td>
                                            <td class="num">{{ $galones($dato['inventario']) }}</td>
                                            <td @class(['num', 'diferencia' => round(abs($dato['diferencia'] ?? 0), 2) != 0.0])>{{ $galones($dato['diferencia']) }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        {{-- COMPROBANTES DESCUADRADOS --}}
        <div class="col-lg-6 col-xl-5" id="comprobantes">
            <div class="card h-100">
                <div class="card-header fw-bold {{ $descuadrados ? 'text-danger' : '' }}">COMPROBANTES DESCUADRADOS</div>
                <div class="card-body">
                    <table class="table table-bordered table-sm mb-0 auditoria-tabla">
                        <thead class="table-light">
                            <tr>
                                <th>COMPROBANTE</th>
                                <th class="num">DIFERENCIA</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($comprobantes as $comprobante)
                                <tr>
                                    <td><a href="{{ route($comprobante['ruta'], $rango) }}">{{ $comprobante['nombre'] }}</a></td>
                                    <td @class(['num', 'diferencia' => $comprobante['diferencia'] != 0.0, 'text-success' => $comprobante['diferencia'] == 0.0])>
                                        {{ '$'.number_format($comprobante['diferencia'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if ($atendidas->isNotEmpty())
        <div class="card mt-3 d-print-none">
            <div class="card-header fw-bold">ALERTAS YA REVISADAS (últimas {{ $atendidas->count() }})</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0 small">
                        <thead>
                            <tr>
                                <th>TURNO</th>
                                <th>FECHA Y HORA — GUARDÓ</th>
                                <th>QUÉ SE QUITÓ</th>
                                <th class="text-center">ESTADO</th>
                                <th>REVISIÓN</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($atendidas as $alerta)
                                <tr>
                                    <td class="text-nowrap">#{{ $alerta->numero_turno }}</td>
                                    <td class="text-nowrap">{{ $alerta->created_at->format('d/m/Y H:i') }} — {{ $alerta->user?->name }}</td>
                                    <td>{{ $alerta->alerta }}</td>
                                    <td class="text-center"><span class="badge text-bg-success">Atendida</span></td>
                                    <td class="text-nowrap">Revisada por {{ $alerta->atendida_por }} el {{ $alerta->atendida_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <script>
        // Filtro por estado de revisión: cada botón muestra u oculta las planillas de ese estado.
        document.addEventListener('DOMContentLoaded', function () {
            const botones = document.querySelectorAll('.filtro-estado');
            const filas = document.querySelectorAll('#tabla-revision tbody tr[data-estado]');
            const sinFilas = document.getElementById('revision-sin-filas');

            function aplicar() {
                const activos = Array.from(botones).filter((boton) => boton.classList.contains('activo')).map((boton) => boton.dataset.estado);
                let visibles = 0;
                filas.forEach((fila) => {
                    fila.hidden = !activos.includes(fila.dataset.estado);
                    visibles += fila.hidden ? 0 : 1;
                });
                sinFilas.hidden = visibles > 0 || filas.length === 0;
            }

            botones.forEach((boton) => boton.addEventListener('click', function () {
                boton.classList.toggle('activo');
                aplicar();
            }));
            aplicar();
        });
    </script>
@endsection
