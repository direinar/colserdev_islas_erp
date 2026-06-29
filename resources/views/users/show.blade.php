@extends('layouts.app')

@section('content')
    <div class="container">

        <div class="d-flex justify-content-between mb-3">
            <h3>Detalle de usuario</h3>
            <a href="{{ route('users.index') }}" class="btn btn-secondary">Volver</a>
        </div>

        <div class="card">
            <div class="card-body">
                <p><strong>Nombre:</strong> {{ $user->name }}</p>
                <p><strong>Email:</strong> {{ $user->email }}</p>
                <p><strong>Rol:</strong> {{ ucfirst(str_replace('_', ' ', $user->role)) }}</p>
                <p><strong>Registrado:</strong> {{ $user->created_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>

    </div>
@endsection
