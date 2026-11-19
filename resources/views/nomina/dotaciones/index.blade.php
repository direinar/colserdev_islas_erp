@extends('layouts.app')

@section('title', 'Control de dotación '.$anio)

@section('content')
    <x-page-header :title="'Control de dotación · '.$anio" icon="bag-check"
        subtitle="Tres entregas al año (30 de abril, 31 de agosto y 20 de diciembre) para quienes ganan hasta dos salarios mínimos.">
        <form method="GET" action="{{ route('nomina.dotaciones.index') }}" class="d-flex gap-2 align-items-end">
            <div>
                <label for="anio" class="form-label">AÑO</label>
                <input id="anio" type="number" name="anio" value="{{ $anio }}" min="2020" max="2100" class="form-control form-control-sm" style="width:6rem;" onchange="this.form.submit()">
            </div>
        </form>
        <button type="button" class="btn btn-outline-light btn-sm" onclick="window.print()"><i class="bi bi-printer" aria-hidden="true"></i> Imprimir</button>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success d-print-none">{{ session('success') }}</div>
    @endif
    @include('nomina.partials.errores-formulario')

    <div class="d-flex flex-wrap gap-3 small mb-2">
        <span><span class="badge text-bg-success">&nbsp;</span> Entregada</span>
        <span><span class="badge text-bg-warning">&nbsp;</span> Pendiente (aún en plazo)</span>
        <span><span class="badge text-bg-danger">&nbsp;</span> Vencida sin entregar</span>
        <span><span class="badge text-bg-secondary">&nbsp;</span> Sin derecho (gana más de 2 SMLMV)</span>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-primary">
                <tr>
                    <th>Empleado</th>
                    @foreach ($entregas as $numero => $entrega)
                        <th class="text-center">{{ $entrega['nombre'] }}<div class="small fw-normal">Límite {{ $entrega['limite']->format('d/m/Y') }}</div></th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($empleados as $empleado)
                    @php($conDerecho = $topeDerecho <= 0 || (float) $empleado->salario_basico <= $topeDerecho)
                    <tr>
                        <td class="fw-semibold">{{ $empleado->nombre_completo }}<div class="small text-muted">{{ $empleado->cargo }}</div></td>
                        @foreach ($entregas as $numero => $entrega)
                            @php($registro = $empleado->dotaciones->firstWhere('entrega', $numero))
                            <td class="text-center">
                                @if ($registro)
                                    <span class="badge text-bg-success">Entregada {{ $registro->fecha_entrega->format('d/m/Y') }}</span>
                                    <div class="small">{{ $registro->elementos }}</div>
                                    <form method="POST" action="{{ route('nomina.dotaciones.destroy', $registro) }}" class="d-print-none" data-sin-aviso onsubmit="return confirm('¿Eliminar esta entrega?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-link btn-sm text-danger p-0">Eliminar</button>
                                    </form>
                                @elseif (! $conDerecho)
                                    <span class="badge text-bg-secondary">Sin derecho</span>
                                @else
                                    <span class="badge {{ $entrega['limite']->isPast() ? 'text-bg-danger' : 'text-bg-warning' }}">{{ $entrega['limite']->isPast() ? 'Vencida' : 'Pendiente' }}</span>
                                    <div class="d-print-none mt-1">
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-entrega" data-bs-toggle="modal" data-bs-target="#modal-entrega"
                                            data-empleado="{{ $empleado->id }}" data-nombre="{{ $empleado->nombre_completo }}" data-entrega="{{ $numero }}" data-titulo="{{ $entrega['nombre'] }}">
                                            <i class="bi bi-plus" aria-hidden="true"></i> Registrar
                                        </button>
                                    </div>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">No hay empleados vinculados en {{ $anio }}.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="modal fade" id="modal-entrega" tabindex="-1" aria-labelledby="modal-entrega-titulo" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('nomina.dotaciones.store') }}" class="modal-content" data-sin-aviso>
                @csrf
                <input type="hidden" name="anio" value="{{ $anio }}">
                <input type="hidden" name="empleado_id" id="entrega-empleado">
                <input type="hidden" name="entrega" id="entrega-numero">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="modal-entrega-titulo">Registrar entrega de dotación</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted" id="entrega-descripcion"></p>
                    <label for="fecha_entrega" class="form-label">Fecha de entrega</label>
                    <input type="date" id="fecha_entrega" name="fecha_entrega" class="form-control mb-2" value="{{ now()->toDateString() }}" required>
                    <label for="elementos" class="form-label">Elementos entregados</label>
                    <input type="text" id="elementos" name="elementos" class="form-control mb-2" maxlength="500" required placeholder="Ej.: 2 camisas, 1 pantalón, 1 par de botas">
                    <label for="observaciones-entrega" class="form-label">Observaciones</label>
                    <input type="text" id="observaciones-entrega" name="observaciones" class="form-control" maxlength="500">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save" aria-hidden="true"></i> Registrar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.querySelectorAll('.btn-entrega').forEach((boton) => boton.addEventListener('click', function () {
            document.getElementById('entrega-empleado').value = boton.dataset.empleado;
            document.getElementById('entrega-numero').value = boton.dataset.entrega;
            document.getElementById('entrega-descripcion').textContent = boton.dataset.titulo + ' de ' + boton.dataset.nombre + ' ({{ $anio }})';
        }));
    </script>
@endsection
