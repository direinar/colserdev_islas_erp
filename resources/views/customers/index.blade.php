@extends('layouts.app')

@section('content')
    <div class="container">

        <x-page-header title="Clientes" icon="people">
            <a href="{{ route('customers.create') }}" class="btn btn-light">Nuevo cliente</a>
        </x-page-header>

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-primary">
                <tr>
                    <th>Nombre</th>
                    <th>Documento</th>
                    <th width="200">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                    <tr>
                        <td>{{ $customer->name }}</td>
                        <td>{{ $customer->document }}</td>
                        <td>
                            <x-action-buttons :showRoute="'customers.show'" :editRoute="'customers.edit'" :deleteRoute="'customers.destroy'" :id="$customer" />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center">No hay clientes registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>

    </div>
@endsection
