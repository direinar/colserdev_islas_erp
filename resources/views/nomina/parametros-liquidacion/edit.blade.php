@extends('layouts.app')

@section('title', 'Editar vigencia de parámetros')

@section('content')
    <div class="container">

        <x-page-header title="Editar vigencia de parámetros" :subtitle="'Vigente desde '.$parametro->vigente_desde->format('d/m/Y')" icon="sliders">
            <a href="{{ route('nomina.parametros-liquidacion.show', $parametro) }}" class="btn btn-light">Volver</a>
        </x-page-header>

        @include('nomina.partials.errores-formulario')

        <form action="{{ route('nomina.parametros-liquidacion.update', $parametro) }}" method="POST">
            @csrf
            @method('PUT')

            @include('nomina.parametros-liquidacion.form')

            <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </form>

    </div>
@endsection
