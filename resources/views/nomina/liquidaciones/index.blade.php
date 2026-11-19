@extends('layouts.app')

@section('title', 'Nómina del mes')

@section('content')
    @php($dinero = fn (float $valor): string => '$ '.number_format($valor, 0, ',', '.'))

    @include('nomina.partials.estilos')

    <x-page-header title="Nómina del mes" icon="cash-coin"
        subtitle="Liquidación mensual: devengados, deducciones, aportes, provisiones, forma de pago, contabilización y desprendibles." />

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @include('nomina.partials.errores-formulario')

    <div class="card mb-3">
        <div class="card-body">
            <form method="POST" action="{{ route('nomina.nominas.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-sm-4 col-md-3">
                    <label for="campo-mes" class="form-label">Mes</label>
                    <select id="campo-mes" name="mes" class="form-select" required>
                        @foreach (range(1, 12) as $mes)
                            <option value="{{ $mes }}" @selected((int) old('mes', $siguiente->month) === $mes)>
                                {{ ucfirst(\Carbon\Carbon::create(2000, $mes, 1)->locale('es')->monthName) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-3 col-md-2">
                    <label for="campo-anio" class="form-label">Año</label>
                    <input id="campo-anio" type="number" name="anio" class="form-control" min="2020" max="2100"
                        value="{{ old('anio', $siguiente->year) }}" required>
                </div>
                <div class="col-sm-5 col-md-3">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-circle" aria-hidden="true"></i> Crear nómina del mes</button>
                </div>
                <div class="col-md-4 small text-muted">
                    Incluye a los empleados vinculados en el mes con 30 días (o los proporcionales si ingresaron o se retiraron)
                    y toma los recargos de la planilla de turnos.
                </div>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-primary">
                <tr>
                    <th>Mes</th>
                    <th>Estado</th>
                    <th class="text-end">Empleados</th>
                    <th class="text-end">Neto a pagar</th>
                    <th class="text-end">Costo total</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($liquidaciones as $liquidacion)
                    <tr>
                        <td class="fw-semibold"><a href="{{ route('nomina.nominas.show', $liquidacion) }}">{{ $liquidacion->nombreDelMes() }}</a></td>
                        <td>
                            <span class="nm-estado {{ $liquidacion->estado }}">{{ $liquidacion->estaAprobada() ? 'Aprobada' : 'Borrador' }}</span>
                            @if ($liquidacion->aprobada_por)
                                <div class="small text-muted">{{ $liquidacion->aprobada_por }} · {{ $liquidacion->aprobada_at?->format('d/m/Y') }}</div>
                            @endif
                        </td>
                        <td class="text-end">{{ $liquidacion->empleados_count }}</td>
                        <td class="text-end fw-semibold">{{ $dinero((float) $liquidacion->neto_total) }}</td>
                        <td class="text-end">{{ $dinero((float) $liquidacion->costo_total) }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('nomina.nominas.show', $liquidacion) }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-{{ $liquidacion->estaAprobada() ? 'eye' : 'pencil-square' }}" aria-hidden="true"></i>
                                {{ $liquidacion->estaAprobada() ? 'Ver' : 'Liquidar' }}
                            </a>
                            <a href="{{ route('nomina.nominas.desprendibles', $liquidacion) }}" class="btn btn-sm btn-outline-secondary" target="_blank">
                                <i class="bi bi-receipt" aria-hidden="true"></i> Desprendibles
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Aún no hay nóminas. Cree la del mes con el formulario de arriba.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
