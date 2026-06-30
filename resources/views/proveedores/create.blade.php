@extends('layouts.app')

@section('content')

    <div class="container">

        <div class="d-flex justify-content-between mb-3">
            <h3>Crear proveedor</h3>
            <a href="{{ route('proveedores.index') }}" class="btn btn-secondary">Volver</a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('proveedores.store') }}" method="POST">
            @csrf

            @include('proveedores.form')

            <button type="submit" class="btn btn-primary">Guardar proveedor</button>
        </form>

    </div>

@endsection
