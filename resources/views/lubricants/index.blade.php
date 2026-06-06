@extends('layouts.app')

@section('content')

<div class="container">

    <div class="d-flex justify-content-between mb-3">

        <h3>Lubricantes</h3>

        <a href="{{ route('lubricants.create') }}"
           class="btn btn-primary">

            Nuevo

        </a>

    </div>

    <table class="table table-bordered table-striped">

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

            @foreach($lubricants as $lubricant)

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

                        @if($lubricant->active)

                            <span class="badge bg-success">
                                ACTIVO
                            </span>

                        @else

                            <span class="badge bg-danger">
                                INACTIVO
                            </span>

                        @endif

                    </td>

                    <td>

                        <div class="d-flex gap-2">

                            <a href="{{ route('lubricants.show', $lubricant) }}"
                               class="btn btn-info btn-sm">

                                Ver

                            </a>

                            <a href="{{ route('lubricants.edit', $lubricant) }}"
                               class="btn btn-warning btn-sm">

                                Editar

                            </a>

                            <form action="{{ route('lubricants.destroy', $lubricant) }}"
                                  method="POST">

                                @csrf
                                @method('DELETE')

                                <button class="btn btn-danger btn-sm">

                                    Eliminar

                                </button>

                            </form>

                        </div>

                    </td>

                </tr>

            @endforeach

        </tbody>

    </table>

</div>

@endsection
