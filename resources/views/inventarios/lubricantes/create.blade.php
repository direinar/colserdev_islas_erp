@extends('layouts.app')

@section('title', 'Inventarios Canastilla')

@section('content')
    <x-page-header title="Inventarios canastilla" icon="clipboard-data"
        subtitle="Kardex al costo promedio por producto. Se alimenta desde la venta de canastilla de la planilla de turnos y desde compras de canastilla; las correcciones se hacen allá.">
        <form method="GET" action="{{ route('inventarios-lubricantes.create') }}">
            <input type="hidden" name="desde" value="{{ $desde }}">
            <input type="hidden" name="hasta" value="{{ $hasta }}">
            <label class="form-label d-block" for="producto">REFERENCIA</label>
            <select name="producto" id="producto" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach ($productos as $opcion)
                    <option value="{{ $opcion }}" @selected($opcion === $producto)>{{ $opcion }}</option>
                @endforeach
            </select>
        </form>
        @if ($producto !== null)
            @include('informes.partials.filtro-rango', [
                'ruta' => 'inventarios-lubricantes.create',
                'rutaExportar' => 'inventarios-lubricantes.exportar',
                'parametros' => ['producto' => $producto],
            ])
        @endif
    </x-page-header>

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
