@extends('layouts.app')

@section('title', 'Inventario Canastilla – Control')

@section('content')
    <x-page-header title="Inventario canastilla – control" icon="clipboard-data"
        subtitle="Consolidado de todas las referencias de lubricantes y urea. Cada salida se valora al costo promedio de su referencia, así los totales de esta hoja son la suma de los totales de cada referencia." />

    @include('inventarios.partials.kardex', [
        'titulo' => 'INVENTARIO CANASTILLA – CONTROL',
        'unidad' => 'UNIDADES',
        'compraRoute' => 'compras-lubricantes.create',
        'mostrarReferencia' => true,
    ])
@endsection
