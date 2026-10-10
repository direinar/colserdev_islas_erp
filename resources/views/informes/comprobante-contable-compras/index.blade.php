@extends('layouts.app')

@section('title', 'Comprobante Contable de Compras')

@php
    use App\Models\ComprobanteCompraCuenta;
    use Illuminate\Support\Carbon;

    $money = fn (float $valor): string => $valor ? number_format($valor, 0, ',', '.') : '';
    $cuadrado = round($totales['debito'], 0) === round($totales['credito'], 0);
@endphp

@section('content')
    <style>
        .cc-tabla th,
        .cc-tabla td {
            white-space: nowrap;
            vertical-align: middle;
        }

        @page {
            size: portrait;
            margin: 8mm;
        }

        @media print {
            nav,
            .navbar {
                display: none !important;
            }

            body > .container-fluid {
                padding: 0 !important;
            }

            .card,
            .card-body {
                border: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
            }

            .cc-tabla {
                font-size: 10px;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>

    <x-page-header title="Comprobante contable de compras" icon="receipt"
        :subtitle="'Desde '.Carbon::parse($desde)->format('d/m/Y').' — hasta '.Carbon::parse($hasta)->format('d/m/Y')">
        @include('informes.partials.filtro-rango', [
            'ruta' => 'informes.comprobante-contable-compras.index',
            'rutaExportar' => 'informes.comprobante-contable-compras.exportar',
        ])
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success d-print-none">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger d-print-none">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @unless ($cuentas->get(ComprobanteCompraCuenta::PROVEEDOR_COMBUSTIBLES)?->proveedor)
        <div class="alert alert-warning d-print-none">
            Falta elegir el proveedor de combustibles en "Cuentas contables": las compras de combustible no registran
            proveedor, por eso el tercero y el NIT salen de esa configuración.
        </div>
    @endunless

    <details class="card mb-3 d-print-none">
        <summary class="card-body py-2 fw-semibold small" style="cursor:pointer;">Cuentas contables del comprobante</summary>
        <form method="POST" action="{{ route('informes.comprobante-contable-compras.cuentas') }}" class="card-body pt-0">
            @csrf
            <input type="hidden" name="desde" value="{{ $desde }}">
            <input type="hidden" name="hasta" value="{{ $hasta }}">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-2">
                    <thead>
                        <tr>
                            <th>Uso</th>
                            <th style="width:12rem;">Cuenta</th>
                            <th style="width:20rem;">Tercero</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cuentas as $clave => $cuenta)
                            <tr>
                                <td class="small">{{ $cuenta->descripcion }}</td>
                                <td>
                                    <input type="text" name="cuentas[{{ $clave }}][cuenta]" class="form-control form-control-sm"
                                        value="{{ $cuenta->cuenta }}" required maxlength="30">
                                </td>
                                <td>
                                    @if ($clave === ComprobanteCompraCuenta::PROVEEDOR_COMBUSTIBLES)
                                        <select name="proveedor_combustibles_id" class="form-select form-select-sm">
                                            <option value="">— Seleccione —</option>
                                            @foreach ($proveedores as $proveedor)
                                                <option value="{{ $proveedor->id }}" @selected($proveedor->id === $cuenta->proveedor_id)>
                                                    {{ $proveedor->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @else
                                        <span class="small text-muted">Proveedor de cada compra</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <button type="submit" class="btn btn-outline-primary btn-sm">Guardar cuentas</button>
        </form>
    </details>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0 cc-tabla">
                <thead style="background-color:#ccccff;">
                    <tr>
                        <th class="text-center">CUENTA</th>
                        <th class="text-center">CONCEPTO</th>
                        <th class="text-center">TERCERO</th>
                        <th class="text-center">NIT</th>
                        <th class="text-center">DEBITO</th>
                        <th class="text-center">CREDITO</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lineas as $linea)
                        <tr>
                            <td>{{ $linea['cuenta'] }}</td>
                            <td>{{ $linea['concepto'] }}</td>
                            <td>{{ $linea['tercero'] }}</td>
                            <td>{{ $linea['nit'] }}</td>
                            <td class="text-end">{{ $money($linea['debito']) }}</td>
                            <td class="text-end">{{ $money($linea['credito']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">No hay compras en el periodo.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="table-secondary fw-bold">
                        <td colspan="4" class="text-end">TOTALES</td>
                        <td class="text-end">{{ number_format($totales['debito'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($totales['credito'], 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    @unless ($cuadrado)
        <div class="alert alert-danger mt-2">Los débitos y los créditos no cuadran; revise las compras del periodo.</div>
    @endunless

    <div class="small text-muted mt-2">Se arma factura por factura desde el módulo de compras. Combustible: inventario
        contra proveedor por el valor total de la factura. Canastilla: inventario por el valor sin IVA, IVA y proveedor
        por el total con IVA. Descuentos compras: proveedor de combustibles contra descuentos compras por el margen
        mayorista del periodo (Informe Margen Mayorista).</div>
@endsection
