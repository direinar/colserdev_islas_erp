@extends('layouts.app')

@section('title', 'Liquidación de contratos')

@section('content')
    @php($dinero = fn (float $valor): string => '$ '.number_format($valor, 0, ',', '.'))

    <x-page-header title="Liquidación de contratos" icon="file-earmark-person"
        subtitle="Liquidador individual de prestaciones sociales al retiro: salarios pendientes, cesantías, intereses, prima y vacaciones.">
        <a href="{{ route('nomina.liquidaciones-contrato.create') }}" class="btn btn-light btn-sm"><i class="bi bi-plus-circle" aria-hidden="true"></i> Nueva liquidación</a>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-primary">
                <tr>
                    <th>Empleado</th>
                    <th>Ingreso</th>
                    <th>Retiro</th>
                    <th>Motivo</th>
                    <th class="text-end">Días del contrato</th>
                    <th class="text-end">Neto a pagar</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($liquidaciones as $liquidacion)
                    <tr>
                        <td class="fw-semibold">{{ $liquidacion->empleado->nombre_completo }}</td>
                        <td>{{ $liquidacion->fecha_ingreso->format('d/m/Y') }}</td>
                        <td>{{ $liquidacion->fecha_retiro->format('d/m/Y') }}</td>
                        <td>{{ $liquidacion->motivo_retiro }}</td>
                        <td class="text-end">{{ number_format($liquidacion->dias_contrato, 0, ',', '.') }}</td>
                        <td class="text-end fw-bold">{{ $dinero($liquidacion->neto_pagar) }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('nomina.liquidaciones-contrato.show', $liquidacion) }}" class="btn btn-sm btn-primary"><i class="bi bi-eye" aria-hidden="true"></i> Ver</a>
                            <a href="{{ route('nomina.liquidaciones-contrato.show', [$liquidacion, 'pdf' => 1]) }}" class="btn btn-sm btn-outline-danger" title="PDF"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No hay liquidaciones de contrato.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
