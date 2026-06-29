@extends('layouts.app')

@section('title', 'Cartera')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1">Cartera</h1>
                <p class="text-muted mb-0">Administración de cartera para Jefe Patios y Administrador.</p>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <p class="mb-3">Este módulo está listo para que empieces a registrar y gestionar los pagos, cobranzas y
                    pendientes de cartera.</p>
                <p class="text-muted mb-0">Para comenzar, agrega aquí las tablas, filtros y acciones que necesitará el equipo
                    de patio.</p>
            </div>
        </div>
    </div>
@endsection
