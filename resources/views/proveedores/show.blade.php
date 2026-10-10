@extends('layouts.app')

@section('content')
    <div class="container">

        <x-page-header title="Detalle de proveedor" icon="truck">
            <a href="{{ route('proveedores.index') }}" class="btn btn-light">Volver</a>
        </x-page-header>

        <div class="card">
            <div class="card-body">
                <div class="mb-3">
                    <strong>Nombre:</strong>
                    <div>{{ $proveedor->name }}</div>
                </div>
                <div class="mb-3">
                    <strong>Documento:</strong>
                    <div>{{ $proveedor->document }}</div>
                </div>
            </div>
        </div>

    </div>
@endsection
