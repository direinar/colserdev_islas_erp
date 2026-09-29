@extends('layouts.app')

@section('title', 'Inventarios Gasolina')

@section('content')
    <div class="pastel-section mb-3">
        <h4 class="mb-0 text-danger fw-bold">INVENTARIOS GASOLINA</h4>
        <small class="text-muted">Kardex al costo promedio. Se alimenta desde la planilla de turnos y desde compras de
            combustible; las correcciones se hacen allá.</small>
    </div>

    @include('inventarios.partials.kardex', [
        'titulo' => 'INVENTARIOS GASOLINA',
        'unidad' => 'GALONES',
        'compraRoute' => 'compras.create',
    ])
@endsection
