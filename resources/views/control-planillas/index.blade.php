@extends('layouts.app')

@section('title', 'Control de planillas')

@section('content')
    <x-page-header title="Control de planillas" icon="shield-exclamation"
        subtitle="Alertas de planillas a las que se les quitaron datos, planillas incompletas y números de turno faltantes." />

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card mb-3">
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
                            <th class="text-center">ACCIÓN</th>
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
                                    <div><a class="small" href="{{ route('control-planillas.historial', $alerta->numero_turno) }}">Historial</a></div>
                                </td>
                                <td class="text-nowrap">{{ $alerta->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $alerta->user?->name }}</td>
                                <td>{{ $alerta->alerta }}</td>
                                <td class="text-center">
                                    <form method="POST" action="{{ route('control-planillas.atender', $alerta) }}" class="mb-0">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success">Revisada</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No hay alertas pendientes.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row g-3">
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
                                    <th>MOTIVO</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($incompletas as $fila)
                                    <tr>
                                        <td><a href="{{ route('turnos.create', ['turno_busqueda' => $fila['turno']->numero_turno]) }}">#{{ $fila['turno']->numero_turno }}</a></td>
                                        <td>{{ $fila['turno']->fecha->format('d/m/Y') }}</td>
                                        <td>{{ $fila['turno']->user?->name ?? $fila['turno']->nombre_vendedor }}</td>
                                        <td>{{ $fila['motivo'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No hay planillas incompletas.</td>
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

    @if ($atendidas->isNotEmpty())
        <div class="card mt-3">
            <div class="card-header fw-bold">ALERTAS YA REVISADAS (últimas {{ $atendidas->count() }})</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0 small">
                        <tbody>
                            @foreach ($atendidas as $alerta)
                                <tr>
                                    <td class="text-nowrap">#{{ $alerta->numero_turno }}</td>
                                    <td class="text-nowrap">{{ $alerta->created_at->format('d/m/Y H:i') }} — {{ $alerta->user?->name }}</td>
                                    <td>{{ $alerta->alerta }}</td>
                                    <td class="text-nowrap">Revisada por {{ $alerta->atendida_por }} el {{ $alerta->atendida_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection
