@extends('layouts.app')

@section('content')
    <div class="container">

        <x-page-header title="Nuevo precio de combustible" icon="fuel-pump">
            <a href="{{ route('fuel-prices.index') }}" class="btn btn-light">Volver</a>
        </x-page-header>

        <div class="card shadow-sm">

            <div class="card-body">

                <form action="{{ route('fuel-prices.store') }}" method="POST">

                    @csrf

                    @include('fuel_prices.form')

                    <div class="d-flex justify-content-end gap-2">

                        <a href="{{ route('fuel-prices.index') }}" class="btn btn-secondary">

                            Cancelar

                        </a>

                        <button type="submit" class="btn btn-primary">

                            Guardar

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>
@endsection
