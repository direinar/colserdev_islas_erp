@extends('layouts.app')

@section('content')

<div class="container">

    <div class="card shadow-sm">

        <div class="card-header d-flex justify-content-between">

            <h4 class="mb-0">
                Detalle Precio Combustible
            </h4>

            <a href="{{ route('fuel-prices.index') }}"
               class="btn btn-secondary btn-sm">

                Volver

            </a>

        </div>

        <div class="card-body">

            <table class="table table-bordered">

                <tr>

                    <th width="250">
                        ID
                    </th>

                    <td>
                        {{ $fuelPrice->id }}
                    </td>

                </tr>

                <tr>

                    <th>
                        Nombre
                    </th>

                    <td>
                        {{ $fuelPrice->name }}
                    </td>

                </tr>

                <tr>

                    <th>
                        Precio
                    </th>

                    <td>
                        $ {{ number_format($fuelPrice->price, 2, ',', '.') }}
                    </td>

                </tr>

                <tr>

                    <th>
                        Fecha Vigencia
                    </th>

                    <td>
                        {{ $fuelPrice->effective_date }}
                    </td>

                </tr>

                <tr>

                    <th>
                        Estado
                    </th>

                    <td>

                        @if($fuelPrice->active)

                            <span class="badge bg-success">
                                ACTIVO
                            </span>

                        @else

                            <span class="badge bg-danger">
                                INACTIVO
                            </span>

                        @endif

                    </td>

                </tr>

                <tr>

                    <th>
                        Creado
                    </th>

                    <td>
                        {{ $fuelPrice->created_at }}
                    </td>

                </tr>

            </table>

        </div>

    </div>

</div>

@endsection
