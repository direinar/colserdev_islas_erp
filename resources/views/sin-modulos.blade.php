@extends('layouts.app')

@section('title', 'Sin módulos asignados')

@section('content')
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-lock display-5 text-secondary" aria-hidden="true"></i>
            <h1 class="h4 mt-3">Su usuario no tiene módulos asignados</h1>
            <p class="text-muted mb-0">Rol: {{ \App\Models\User::etiquetaRol(auth()->user()->role) }}. Solicite al
                administrador o al gerente que le asigne el acceso que necesita.</p>
        </div>
    </div>
@endsection
