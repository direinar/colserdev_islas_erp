@extends('layouts.app')

@section('content')
    <div class="container">

        <x-page-header title="Detalle de banco" icon="bank">
            <a href="{{ route('bancos.index') }}" class="btn btn-light">Volver</a>
        </x-page-header>

        <div class="card">
            <div class="card-body">
                <div class="mb-3">
                    <strong>Nombre:</strong>
                    <div>{{ $banco->name }}</div>
                </div>
            </div>
        </div>

    </div>
@endsection
