@extends('layouts.app')

@section('content')

<div class="container">

    <div class="card">

        <div class="card-header">
            Editar Lubricante
        </div>

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
