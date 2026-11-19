@extends('layouts.app')

@section('title', 'Nuevo turno')

@section('content')
    <div class="container">

        <x-page-header title="Nuevo turno" icon="clock">
            <a href="{{ route('nomina.turnos.index') }}" class="btn btn-light">Volver</a>
        </x-page-header>

        @include('nomina.partials.errores-formulario')

        <form action="{{ route('nomina.turnos.store') }}" method="POST">
            @csrf

            @include('nomina.turnos.form')

            <button type="submit" class="btn btn-primary">Guardar turno</button>
        </form>

    </div>
@endsection
