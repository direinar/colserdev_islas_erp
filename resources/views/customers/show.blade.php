@extends('layouts.app')

@section('content')
    <div class="container">

        <x-page-header title="Detalle de cliente" icon="people">
            <a href="{{ route('customers.index') }}" class="btn btn-light">Volver</a>
        </x-page-header>

        <div class="card">
            <div class="card-body">
                <div class="mb-3">
                    <strong>Nombre:</strong>
                    <div>{{ $customer->name }}</div>
                </div>
                <div class="mb-3">
                    <strong>Documento:</strong>
                    <div>{{ $customer->document }}</div>
                </div>
            </div>
        </div>

    </div>
@endsection
