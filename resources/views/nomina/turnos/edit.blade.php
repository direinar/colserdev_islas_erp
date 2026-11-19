@extends('layouts.app')

@section('title', 'Editar turno')

@section('content')
    <div class="container">

        <x-page-header title="Editar turno" :subtitle="$turno->nombre" icon="clock">
            <a href="{{ route('nomina.turnos.index') }}" class="btn btn-light">Volver</a>
        </x-page-header>

        @include('nomina.partials.errores-formulario')

        <form action="{{ route('nomina.turnos.update', $turno) }}" method="POST">
            @csrf
            @method('PUT')

            @include('nomina.turnos.form')

            <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </form>

    </div>
@endsection
