@extends('layouts.app')

@section('content')

    <div class="container">

        <x-page-header title="Editar proveedor" icon="truck">
            <a href="{{ route('proveedores.index') }}" class="btn btn-light">Volver</a>
        </x-page-header>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('proveedores.update', $proveedor) }}" method="POST">
            @csrf
            @method('PUT')

            @include('proveedores.form')

            <button type="submit" class="btn btn-primary">Actualizar proveedor</button>
        </form>

    </div>

@endsection
