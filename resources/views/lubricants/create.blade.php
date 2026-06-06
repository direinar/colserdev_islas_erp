@extends('layouts.app')

@section('content')

<div class="container">

    <div class="card">

        <div class="card-header">
            Nuevo Lubricante
        </div>

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
