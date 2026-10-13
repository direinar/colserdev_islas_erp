@extends('layouts.app')

@section('title', 'Editar compra')

@section('content')
    <x-page-header title="Editar compra de combustible" icon="fuel-pump"
        subtitle="Los inventarios de gasolina y ACPM se recalculan con los cambios.">
        <a href="{{ route('compras.create', ['desde' => $compra->fecha->copy()->startOfMonth()->toDateString(), 'hasta' => $compra->fecha->copy()->endOfMonth()->toDateString()]) }}"
            class="btn btn-outline-light">Volver</a>
        <button type="submit" form="compra-edit-form" class="btn btn-light">
            <i class="bi bi-save" aria-hidden="true"></i> Guardar cambios
        </button>
    </x-page-header>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="compra-edit-form" method="POST" action="{{ route('compras.update', $compra) }}">
        @csrf
        @method('PUT')

        <x-erp-card title="COMPRA {{ $compra->factura }}">
            <div class="row g-3 p-3">
                <div class="col-6 col-md-3">
                    <label for="fecha" class="form-label small mb-1">Fecha</label>
                    <input type="date" id="fecha" name="fecha" class="form-control form-control-sm"
                        value="{{ old('fecha', $valores['fecha']) }}">
                </div>
                <div class="col-6 col-md-3">
                    <label for="factura" class="form-label small mb-1">No. factura</label>
                    <input type="text" id="factura" name="factura" class="form-control form-control-sm"
                        value="{{ old('factura', $valores['factura']) }}">
                </div>
                <div class="col-12 col-md-6">
                    <label for="proveedor_id" class="form-label small mb-1">Proveedor</label>
                    <select id="proveedor_id" name="proveedor_id" class="form-select form-select-sm">
                        <option value="">Seleccione proveedor</option>
                        @foreach ($proveedores as $proveedor)
                            <option value="{{ $proveedor->id }}" @selected(old('proveedor_id', $valores['proveedor_id']) == $proveedor->id)>
                                {{ $proveedor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label for="gasolina" class="form-label small mb-1">Gasolina - galones</label>
                    <input type="text" id="gasolina" name="gasolina" class="form-control form-control-sm text-end"
                        inputmode="decimal" value="{{ old('gasolina', $valores['gasolina']) }}">
                </div>
                <div class="col-6 col-md-3">
                    <label for="distribucion_gasolina" class="form-label small mb-1">Gasolina - distrib. costo</label>
                    <input type="text" id="distribucion_gasolina" name="distribucion_gasolina"
                        class="form-control form-control-sm text-end" inputmode="decimal"
                        value="{{ old('distribucion_gasolina', $valores['distribucion_gasolina']) }}">
                </div>
                <div class="col-6 col-md-3">
                    <label for="acpm" class="form-label small mb-1">ACPM - galones</label>
                    <input type="text" id="acpm" name="acpm" class="form-control form-control-sm text-end"
                        inputmode="decimal" value="{{ old('acpm', $valores['acpm']) }}">
                </div>
                <div class="col-6 col-md-3">
                    <label for="distribucion_acpm" class="form-label small mb-1">ACPM - distrib. costo</label>
                    <input type="text" id="distribucion_acpm" name="distribucion_acpm"
                        class="form-control form-control-sm text-end" inputmode="decimal"
                        value="{{ old('distribucion_acpm', $valores['distribucion_acpm']) }}">
                </div>
            </div>
        </x-erp-card>
    </form>
@endsection
