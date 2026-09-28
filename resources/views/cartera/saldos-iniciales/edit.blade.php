@extends('layouts.app')

@section('title', 'Editar saldo inicial')

@section('content')
    <div class="container">

        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
            <h3>Editar saldo inicial</h3>
            <a href="{{ route('cartera-saldos-iniciales.index') }}" class="btn btn-secondary">Volver</a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('cartera-saldos-iniciales.update', $saldoInicial) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Cliente</label>
                    <input type="text" class="form-control" readonly
                        value="{{ $saldoInicial->customer?->name }} - {{ $saldoInicial->customer?->document }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label" for="fecha">Fecha de corte</label>
                    <input type="date" name="fecha" id="fecha" class="form-control" required
                        value="{{ old('fecha', $saldoInicial->fecha?->toDateString()) }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label" for="valor">Valor</label>
                    <input type="text" name="valor" id="valor" class="form-control text-end" inputmode="decimal" required
                        value="{{ old('valor', number_format((float) $saldoInicial->saldo_inicial, 0, ',', '.')) }}">
                    <small class="text-muted">Negativo = saldo a favor del cliente.</small>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Actualizar saldo inicial</button>
        </form>

    </div>
@endsection
