@extends('layouts.app')

@section('content')
    <div class="container">

        <div class="crud-create-wrap">
            <h3>Canastilla</h3>
            <a href="{{ route('lubricants.create') }}" class="btn btn-primary">Nuevo</a>
        </div>

        <table class="crud-table">

            <thead class="table-primary">

                <tr>

                    <th>Referencia</th>
                    <th>Venta</th>
                    <th>IVA</th>
                    <th>Total</th>
                    <th>Costo</th>
                    <th>Proveedor</th>
                    <th>Estado</th>
                    <th width="220">Acciones</th>

                </tr>

            </thead>

            <tbody>

                @foreach ($lubricants as $lubricant)
                    <tr>

                        <td>
                            {{ $lubricant->reference }}
                        </td>

                        <td>
                            {{ number_format($lubricant->sale_price, 2) }}
                        </td>

                        <td>
                            {{ number_format($lubricant->iva, 2) }}
                        </td>

                        <td>
                            {{ number_format($lubricant->total, 2) }}
                        </td>

                        <td>
                            {{ number_format($lubricant->cost_price, 2) }}
                        </td>

                        <td>
                            {{ $lubricant->supplier }}
                        </td>

                        <td>
                        <td>
                            @if ($lubricant->active)
                                <span class="badge-pill badge-active">ACTIVO</span>
                            @else
                                <span class="badge-pill badge-inactive">INACTIVO</span>
                            @endif
                        </td>

                        <td>
                            <x-action-buttons :showRoute="'lubricants.show'" :editRoute="'lubricants.edit'" :deleteRoute="'lubricants.destroy'" :id="$lubricant" />
                        </td>

                    </tr>
                @endforeach

            </tbody>

        </table>

    </div>
@endsection
