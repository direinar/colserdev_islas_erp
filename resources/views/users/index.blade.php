@extends('layouts.app')

@section('content')
    <div class="container">

        <x-page-header title="Usuarios" icon="person-gear">
            <a href="{{ route('users.create') }}" class="btn btn-light">Nuevo usuario</a>
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
                    <th>Email</th>
                    <th>Rol</th>
                    <th width="250">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $user->role)) }}</td>
                        <td>
                            <x-action-buttons :showRoute="'users.show'" :editRoute="'users.edit'" :deleteRoute="'users.destroy'" :id="$user" />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">No hay usuarios registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>

    </div>
@endsection
