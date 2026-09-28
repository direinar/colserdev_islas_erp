@extends('layouts.app')

@section('title', 'Saldos iniciales de cartera')

@php
    $money = fn ($value) => number_format((float) $value, 0, ',', '.');
@endphp

@section('content')
    <div class="container">

        <div class="d-flex justify-content-between mb-3">
            <div>
                <h3 class="mb-0">Saldos iniciales de cartera</h3>
                <small class="text-muted">
                    Saldo de cada cliente al arrancar el sistema. Positivo = el cliente debe; negativo = saldo a favor.
                </small>
            </div>
            <a href="{{ route('cartera.index') }}" class="btn btn-secondary align-self-start">Estado de cuenta</a>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('errores_importacion'))
            <div class="alert alert-warning">
                <strong>Filas no cargadas:</strong>
                <ul class="mb-0">
                    @foreach (session('errores_importacion') as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row g-3 mb-3">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header fw-bold">Registrar saldo inicial</div>
                    <div class="card-body">
                        <form action="{{ route('cartera-saldos-iniciales.store') }}" method="POST">
                            @csrf
                            <div class="row g-2 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label" for="customer-id">Cliente</label>
                                    <select name="customer_id" id="customer-id" class="form-select" required>
                                        <option value="">Seleccione cliente</option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->id }}" @selected((int) old('customer_id') === $customer->id)>
                                                {{ $customer->name }} - {{ $customer->document }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="fecha">Fecha de corte</label>
                                    <input type="date" name="fecha" id="fecha" class="form-control"
                                        value="{{ old('fecha') }}" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="valor">Valor</label>
                                    <input type="text" name="valor" id="valor" class="form-control text-end"
                                        inputmode="decimal" value="{{ old('valor') }}" placeholder="-150.000" required>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">Guardar</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header fw-bold">Importar desde Excel</div>
                    <div class="card-body">
                        <form action="{{ route('cartera-saldos-iniciales.importar') }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            <div class="input-group">
                                <input type="file" name="archivo" class="form-control" accept=".xlsx,.xls,.csv" required>
                                <button type="submit" class="btn btn-outline-primary">Importar</button>
                            </div>
                            <small class="text-muted d-block mt-2">
                                Descargue la plantilla (trae todos los clientes), llene fecha de corte
                                (aaaa-mm-dd o dd/mm/aaaa) y valor solo de los clientes con saldo, y súbala aquí.
                                Si el cliente ya tiene saldo inicial, se reemplaza.
                            </small>
                            <a href="{{ route('cartera-saldos-iniciales.plantilla') }}" class="btn btn-sm btn-outline-success mt-2">
                                <i class="bi bi-file-earmark-excel"></i> Descargar plantilla
                            </a>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <table class="table table-bordered table-striped">
            <thead class="table-primary">
                <tr>
                    <th>Cliente</th>
                    <th>NIT / Documento</th>
                    <th>Fecha de corte</th>
                    <th class="text-end">Saldo inicial</th>
                    <th width="200">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($saldos as $saldo)
                    <tr>
                        <td>{{ $saldo->customer?->name }}</td>
                        <td>{{ $saldo->customer?->document }}</td>
                        <td>{{ $saldo->fecha?->format('d/m/Y') }}</td>
                        <td @class(['text-end', 'text-danger fw-bold' => (float) $saldo->saldo_inicial < 0])>
                            {{ $money($saldo->saldo_inicial) }}
                        </td>
                        <td>
                            <x-action-buttons :editRoute="'cartera-saldos-iniciales.edit'" :deleteRoute="'cartera-saldos-iniciales.destroy'" :id="$saldo" />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center">No hay saldos iniciales registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    </div>
@endsection
