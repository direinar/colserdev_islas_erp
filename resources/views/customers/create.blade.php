@extends('layouts.app')

@section('content')

    <div class="container">

        <x-page-header title="Crear cliente" icon="people">
            <a href="{{ route('customers.index') }}" class="btn btn-light">Volver</a>
        </x-page-header>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('customers.store') }}" method="POST">
            @csrf

            @include('customers.form')

            <button type="submit" class="btn btn-primary">Guardar cliente</button>
        </form>

    </div>

@endsection
