@extends('layouts.app')

@section('content')

<div class="container">

    <x-page-header title="Nueva referencia de canastilla" icon="box-seam">
        <a href="{{ route('lubricants.index') }}" class="btn btn-light">Volver</a>
    </x-page-header>

    <div class="card">

        <div class="card-body">

            <form action="{{ route('lubricants.store') }}"
                  method="POST">

                @csrf

                @include('lubricants.form')

                <button class="btn btn-primary">
                    Guardar
                </button>

            </form>

        </div>

    </div>

</div>

@endsection
