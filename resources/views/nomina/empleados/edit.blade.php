@extends('layouts.app')

@section('title', 'Editar empleado')

@section('content')
    <div class="container">

        <x-page-header title="Editar empleado" :subtitle="$empleado->nombre_completo" icon="person-gear">
            <a href="{{ route('nomina.empleados.show', $empleado) }}" class="btn btn-light">Volver</a>
        </x-page-header>

        @include('nomina.partials.errores-formulario')

        <form action="{{ route('nomina.empleados.update', $empleado) }}" method="POST">
            @csrf
            @method('PUT')

            @include('nomina.empleados.form')

            <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </form>

    </div>
@endsection
