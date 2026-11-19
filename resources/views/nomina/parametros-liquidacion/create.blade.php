@extends('layouts.app')

@section('title', 'Nueva vigencia de parámetros')

@section('content')
    <div class="container">

        <x-page-header title="Nueva vigencia de parámetros" icon="sliders"
            subtitle="Se precargan los valores de la vigencia más reciente; cambie solo lo que se actualizó.">
            <a href="{{ route('nomina.parametros-liquidacion.index') }}" class="btn btn-light">Volver</a>
        </x-page-header>

        @include('nomina.partials.errores-formulario')

        <form action="{{ route('nomina.parametros-liquidacion.store') }}" method="POST">
            @csrf

            @include('nomina.parametros-liquidacion.form')

            <button type="submit" class="btn btn-primary">Guardar vigencia</button>
        </form>

    </div>
@endsection
