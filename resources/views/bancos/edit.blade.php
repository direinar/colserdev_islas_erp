@extends('layouts.app')

@section('content')

    <div class="container">

        <x-page-header title="Editar banco" icon="bank">
            <a href="{{ route('bancos.index') }}" class="btn btn-light">Volver</a>
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

        <form action="{{ route('bancos.update', $banco) }}" method="POST">
            @csrf
            @method('PUT')

            @include('bancos.form')

            <button type="submit" class="btn btn-primary">Actualizar banco</button>
        </form>

    </div>

@endsection
