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

    <style>
        .compra-edicion .compra-calc {
            background: #f2f2f2;
            font-weight: 700;
            text-align: right;
        }

        .compra-edicion .grupo-gasolina { background: #fbe5d6; }
        .compra-edicion .grupo-acpm { background: #e2efda; }
    </style>

    <form id="compra-edit-form" method="POST" action="{{ route('compras.update', $compra) }}" class="compra-edicion">
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
            </div>

            <div class="row g-3 px-3 pb-3">
                @foreach (['gasolina' => 'GASOLINA', 'acpm' => 'ACPM'] as $combustible => $nombre)
                    <div class="col-12 col-xl-6">
                        <fieldset class="border rounded p-2 h-100">
                            <legend class="float-none w-auto px-2 mb-0 small fw-bold grupo-{{ $combustible }}">{{ $nombre }}</legend>
                            <div class="row g-2">
                                @foreach (['' => 'Galones', 'base19_' => 'Base 19%', 'base5_' => 'Base 5%', 'costo_producto_' => 'Costo producto', 'sobretasa_' => 'Sobretasa', 'soldicom_' => 'Soldicom'] as $prefijo => $titulo)
                                    <div class="col-6 col-md-4">
                                        <label for="{{ $prefijo.$combustible }}" class="form-label small mb-1">{{ $titulo }}</label>
                                        <input type="text" id="{{ $prefijo.$combustible }}" name="{{ $prefijo.$combustible }}"
                                            data-campo="{{ $prefijo.$combustible }}" class="form-control form-control-sm text-end" inputmode="decimal"
                                            value="{{ old($prefijo.$combustible, $valores[$prefijo.$combustible]) }}">
                                    </div>
                                @endforeach
                                <div class="col-6 col-md-4">
                                    <span class="form-label small mb-1 d-block">Liquidación IVA</span>
                                    <output class="form-control form-control-sm compra-calc" data-calc="iva_{{ $combustible }}">0</output>
                                </div>
                                <div class="col-6 col-md-4">
                                    <span class="form-label small mb-1 d-block">Costo {{ $combustible === 'acpm' ? 'ACPM' : 'gasolina' }}</span>
                                    <output class="form-control form-control-sm compra-calc" data-calc="costo_{{ $combustible }}">0</output>
                                </div>
                                <div class="col-6 col-md-4">
                                    <span class="form-label small mb-1 d-block">Costo unitario</span>
                                    <output class="form-control form-control-sm compra-calc" data-calc="unitario_{{ $combustible }}">0</output>
                                </div>
                            </div>
                        </fieldset>
                    </div>
                @endforeach
            </div>

            <div class="small px-3 pb-3">
                Total de la factura: <strong data-calc="vr_total_fra">0</strong>
            </div>
        </x-erp-card>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const formulario = document.getElementById('compra-edit-form');
            const leer = (valor) => Number(String(valor ?? '').trim().replace(/\s+/g, '').replace(/\./g, '').replace(/,/g, '.')) || 0;
            const pesos = new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0 });
            const unitario = new Intl.NumberFormat('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const valor = (campo) => leer(formulario.querySelector(`[data-campo="${campo}"]`)?.value);
            const mostrar = (campo, texto) => formulario.querySelector(`[data-calc="${campo}"]`).textContent = texto;

            function liquidar() {
                let total = 0;
                for (const c of ['gasolina', 'acpm']) {
                    const iva = valor(`base19_${c}`) * {{ \App\Models\Compra::TARIFA_IVA_GENERAL }} + valor(`base5_${c}`) * {{ \App\Models\Compra::TARIFA_IVA_REDUCIDA }};
                    const costo = iva + valor(`costo_producto_${c}`) + valor(`sobretasa_${c}`) + valor(`soldicom_${c}`);
                    const galones = valor(c);
                    total += costo;
                    mostrar(`iva_${c}`, pesos.format(iva));
                    mostrar(`costo_${c}`, pesos.format(costo));
                    mostrar(`unitario_${c}`, unitario.format(galones > 0 ? costo / galones : 0));
                }
                mostrar('vr_total_fra', pesos.format(total));
            }

            formulario.addEventListener('input', liquidar);
            liquidar();
        });
    </script>
@endsection
