@extends('layouts.app')

@section('content')
    <div class="container">

        <x-page-header title="Detalle de usuario" icon="person-gear">
            <a href="{{ route('users.index') }}" class="btn btn-light">Volver</a>
        </x-page-header>

        <div class="card">
            <div class="card-body">
                <p><strong>Nombre:</strong> {{ $user->name }}</p>
                <p><strong>Email:</strong> {{ $user->email }}</p>
                <p><strong>Rol:</strong> {{ App\Models\User::etiquetaRol($user->role) }}</p>
                <p><strong>Registrado:</strong> {{ $user->created_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>

    </div>
@endsection
