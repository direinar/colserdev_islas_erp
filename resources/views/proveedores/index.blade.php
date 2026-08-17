@extends('layouts.app')

@section('content')
    <div class="container">

        <div class="d-flex justify-content-between mb-3">
            <h3>Proveedores</h3>
            <a href="{{ route('proveedores.create') }}" class="btn btn-primary">Nuevo proveedor</a>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <table class="table table-bordered table-striped">
            <thead class="table-primary">
                <tr>
                    <th>Nombre</th>
                    <th>Documento</th>
                    <th width="200">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($proveedores as $proveedor)
                    <tr>
                        <td>{{ $proveedor->name }}</td>
                        <td>{{ $proveedor->document }}</td>
                        <td>
                            <x-action-buttons :showRoute="'proveedores.show'" :editRoute="'proveedores.edit'" :deleteRoute="'proveedores.destroy'" :id="$proveedor" />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center">No hay proveedores registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    </div>
@endsection
