@extends('layouts.app')

@section('title', 'Inventarios Gasolina')

@section('content')
    <x-page-header title="Inventarios gasolina" icon="clipboard-data"
        subtitle="Kardex al costo promedio. Se alimenta desde la planilla de turnos y desde compras de combustible; las correcciones se hacen allá.">
        @include('informes.partials.filtro-rango', [
            'ruta' => 'inventarios-gasolina.create',
            'rutaExportar' => 'inventarios-gasolina.exportar',
        ])
    </x-page-header>

    @include('inventarios.partials.kardex', [
        'titulo' => 'INVENTARIOS GASOLINA',
        'unidad' => 'GALONES',
        'compraRoute' => 'compras.create',
    ])
@endsection
