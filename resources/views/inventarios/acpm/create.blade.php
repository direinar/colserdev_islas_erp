@extends('layouts.app')

@section('title', 'Inventarios ACPM')

@section('content')
    <x-page-header title="Inventarios ACPM" icon="clipboard-data"
        subtitle="Kardex al costo promedio. Se alimenta desde la planilla de turnos y desde compras de combustible; las correcciones se hacen allá." />

    @include('inventarios.partials.kardex', [
        'titulo' => 'INVENTARIOS ACPM',
        'unidad' => 'GALONES',
        'compraRoute' => 'compras.create',
    ])
@endsection
