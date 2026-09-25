@extends('layouts.app')

@section('content')
    <div class="container">

        <div class="d-flex justify-content-between mb-3">
            <h3>Bancos</h3>
            <a href="{{ route('bancos.create') }}" class="btn btn-primary">Nuevo banco</a>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <table class="table table-bordered table-striped">
            <thead class="table-primary">
                <tr>
                    <th>Nombre</th>
                    <th width="200">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bancos as $banco)
                    <tr>
                        <td>{{ $banco->name }}</td>
                        <td>
                            <x-action-buttons :showRoute="'bancos.show'" :editRoute="'bancos.edit'" :deleteRoute="'bancos.destroy'" :id="$banco" />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="text-center">No hay bancos registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    </div>
@endsection
