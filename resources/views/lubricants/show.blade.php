@extends('layouts.app')

@section('content')

<div class="container">

    <x-page-header title="Detalle de referencia de canastilla" icon="box-seam">
        <a href="{{ route('lubricants.index') }}" class="btn btn-light">Volver</a>
    </x-page-header>

    <div class="card">

        <div class="card-body">

            <div class="table-responsive">
            <table class="table table-bordered">

                <tr>
                    <th>Referencia</th>
                    <td>{{ $lubricant->reference }}</td>
                </tr>

                <tr>
                    <th>Precio Venta</th>
                    <td>{{ number_format($lubricant->sale_price, 2) }}</td>
                </tr>

                <tr>
                    <th>IVA</th>
                    <td>{{ number_format($lubricant->iva, 2) }}</td>
                </tr>

                <tr>
                    <th>Total</th>
                    <td>{{ number_format($lubricant->total, 2) }}</td>
                </tr>

                <tr>
                    <th>Costo</th>
                    <td>{{ number_format($lubricant->cost_price, 2) }}</td>
                </tr>

                <tr>
                    <th>Proveedor</th>
                    <td>{{ $lubricant->supplier }}</td>
                </tr>

            </table>
            </div>

        </div>

    </div>

</div>

@endsection
