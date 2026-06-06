@extends('layouts.app')

@section('content')
    {{-- HEADER --}}

    <div class="bg-dark text-white p-2 mb-3">

        <div class="row align-items-center">

            <div class="col-md-6">

                <h4 class="mb-0">
                    PLANTILLA DE TURNOS
                </h4>

            </div>

            <div class="col-md-6 text-end">

                FECHA:

                <input type="date" class="form-control form-control-sm d-inline-block w-auto">

                TURNO:

                <input type="number" class="form-control form-control-sm d-inline-block" style="width: 80px">

            </div>

        </div>

    </div>

    {{-- CONTENIDO --}}

    <div class="row">

        {{-- COLUMNA IZQUIERDA --}}

        <div class="col-lg-4">

            @include('planillas.turnos.partials.ventas')

            @include('planillas.turnos.partials.surtidores')

            @include('planillas.turnos.partials.lubricantes')

            @include('planillas.turnos.partials.resumen')

            @include('planillas.turnos.partials.sobrantes')

        </div>

        {{-- CENTRO --}}

        <div class="col-lg-8">

            {{-- Ocupa todo el ancho --}}
            @include('planillas.turnos.partials.medios_pago')

            {{-- Fila inferior --}}
            <div class="row mt-3">

                <div class="col-md-4">
                    @include('planillas.turnos.partials.qr')
                </div>

                <div class="col-md-4">
                    @include('planillas.turnos.partials.recaudos')
                </div>

                <div class="col-md-4">
                    @include('planillas.turnos.partials.transferencias')
                </div>

            </div>
            {{-- Fila inferior --}}
            <div class="row mt-3">

                <div class="col-md-4">
                    @include('planillas.turnos.partials.gasolina_eds')
                </div>

                <div class="col-md-4">
                    @include('planillas.turnos.partials.varios')
                </div>

            </div>

            {{-- Resumen y Recaudos Administración --}}
            <div class="row mt-3">
                <div class="col-lg-6">
                    @include('planillas.turnos.partials.resumen_recibido_turno')
                </div>
                <div class="col-lg-6">
                    @include('planillas.turnos.partials.recaudos_admin')
                </div>
            </div>
        </div>
    </div>
@endsection
