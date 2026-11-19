@extends('layouts.app')

@section('title', 'Nuevo empleado')

@section('content')
    <div class="container">

        <x-page-header title="Nuevo empleado" icon="person-plus">
            <a href="{{ route('nomina.empleados.index') }}" class="btn btn-light">Volver</a>
        </x-page-header>

        @include('nomina.partials.errores-formulario')

        <form action="{{ route('nomina.empleados.store') }}" method="POST">
            @csrf

            @include('nomina.empleados.form')

            <button type="submit" class="btn btn-primary">Guardar empleado</button>
        </form>

    </div>
@endsection
