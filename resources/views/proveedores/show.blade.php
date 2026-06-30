@extends('layouts.app')

@section('content')
    <div class="container">

        <div class="d-flex justify-content-between mb-3">
            <h3>Detalle de proveedor</h3>
            <a href="{{ route('proveedores.index') }}" class="btn btn-secondary">Volver</a>
        </div>

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
