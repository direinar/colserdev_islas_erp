@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">

    <h1 class="h3">
        Dashboard
    </h1>

    <x-button color="primary">
        Nuevo
    </x-button>

</div>

<div class="row">

    <div class="col-md-4 mb-4">

        <x-card title="Usuarios">

            <h2>
                120
            </h2>

        </x-card>

    </div>

    <div class="col-md-4 mb-4">

        <x-card title="Ventas">

            <h2>
                350
            </h2>

        </x-card>

    </div>

    <div class="col-md-4 mb-4">

        <x-card title="Productos">

            <h2>
                89
            </h2>

        </x-card>

    </div>

</div>

@endsection
