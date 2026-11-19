@extends('layouts.app')

@section('title', 'Liquidación de '.$liquidacion->empleado->nombre_completo)

@section('content')
    <div class="container" style="max-width: 900px;">
        <x-page-header :title="'Liquidación de contrato · '.$liquidacion->empleado->nombre_completo" icon="file-earmark-person"
            :subtitle="'Elaborada por '.($liquidacion->elaborada_por ?? '—').' el '.$liquidacion->created_at->format('d/m/Y H:i')">
            <button type="button" class="btn btn-outline-light btn-sm" onclick="window.print()"><i class="bi bi-printer" aria-hidden="true"></i> Imprimir</button>
            <a href="{{ route('nomina.liquidaciones-contrato.show', [$liquidacion, 'pdf' => 1]) }}" class="btn btn-outline-light btn-sm"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> PDF</a>
            <a href="{{ route('nomina.liquidaciones-contrato.index') }}" class="btn btn-outline-light btn-sm">Volver</a>
        </x-page-header>

        @if (session('success'))
            <div class="alert alert-success d-print-none">{{ session('success') }}</div>
        @endif

        <div class="card">
            <div class="card-body">
                @include('nomina.liquidaciones-contrato.documento', ['paraPdf' => false])
            </div>
        </div>

        <form method="POST" action="{{ route('nomina.liquidaciones-contrato.destroy', $liquidacion) }}" class="mt-3 d-print-none text-end" data-sin-aviso
            onsubmit="return confirm('¿Eliminar esta liquidación? Podrá elaborarla de nuevo.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash" aria-hidden="true"></i> Eliminar y volver a liquidar</button>
        </form>
    </div>
@endsection
