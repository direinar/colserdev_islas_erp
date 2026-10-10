@extends('layouts.app')

@section('content')
    <div class="container">

        <x-page-header title="Editar usuario" icon="person-gear">
            <a href="{{ route('users.index') }}" class="btn btn-light">Volver</a>
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

        <form action="{{ route('users.update', $user) }}" method="POST">
            @csrf
            @method('PUT')

            @include('users.form')

            <button type="submit" class="btn btn-primary">Actualizar usuario</button>
        </form>

    </div>
@endsection
