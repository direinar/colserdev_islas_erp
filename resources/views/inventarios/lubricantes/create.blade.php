@extends('layouts.app')

@section('title', 'Inventarios Canastilla')

@section('content')
    <div class="pastel-section mb-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
            <div>
                <h4 class="mb-0 text-warning-emphasis fw-bold">INVENTARIOS CANASTILLA</h4>
                <small class="text-muted">Kardex al costo promedio por producto. Se alimenta desde la venta de canastilla
                    de la planilla de turnos y desde compras de lubricantes; las correcciones se hacen allá.</small>
            </div>
            <form method="GET" action="{{ route('inventarios-lubricantes.create') }}" class="d-flex gap-2">
                <select name="producto" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach ($productos as $opcion)
                        <option value="{{ $opcion }}" @selected($opcion === $producto)>{{ $opcion }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    @if ($producto === null)
        <div class="alert alert-info">No hay productos de canastilla registrados.</div>
    @else
        @include('inventarios.partials.kardex', [
            'titulo' => 'INVENTARIOS ' . $producto,
            'unidad' => 'UNIDADES',
            'compraRoute' => 'compras-lubricantes.create',
        ])
    @endif
@endsection
