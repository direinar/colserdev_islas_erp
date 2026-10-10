@extends('layouts.app')

@section('title', 'Inventario Canastilla – Control')

@section('content')
    <div class="pastel-section mb-3">
        <h4 class="mb-0 text-warning-emphasis fw-bold">INVENTARIO CANASTILLA – CONTROL</h4>
        <small class="text-muted">Consolidado de todas las referencias de lubricantes y urea. Cada salida se valora al
            costo promedio de su referencia, así los totales de esta hoja son la suma de los totales de cada referencia.</small>
    </div>

    @include('inventarios.partials.kardex', [
        'titulo' => 'INVENTARIO CANASTILLA – CONTROL',
        'unidad' => 'UNIDADES',
        'compraRoute' => 'compras-lubricantes.create',
        'mostrarReferencia' => true,
    ])
@endsection
