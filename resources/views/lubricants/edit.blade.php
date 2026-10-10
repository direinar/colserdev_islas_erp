@extends('layouts.app')

@section('content')

<div class="container">

    <x-page-header title="Editar referencia de canastilla" icon="box-seam">
        <a href="{{ route('lubricants.index') }}" class="btn btn-light">Volver</a>
    </x-page-header>

    <div class="card">

        <div class="card-body">

            <form action="{{ route('lubricants.update', $lubricant) }}"
                  method="POST">

                @csrf
                @method('PUT')

                @include('lubricants.form')

                <button class="btn btn-warning">
                    Actualizar
                </button>

            </form>

        </div>

    </div>

</div>

@endsection
