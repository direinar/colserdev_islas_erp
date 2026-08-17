@extends('layouts.app')

@section('content')
    <div class="container">

        <div class="d-flex justify-content-between align-items-center mb-4">

            <h2 class="mb-0">
                Precios de Combustible
            </h2>

            <a href="{{ route('fuel-prices.create') }}" class="btn btn-primary">

                <i class="bi bi-plus-circle"></i>
                Nuevo Registro

            </a>

        </div>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <div class="card shadow-sm">

            <div class="card-body p-0">

                <table class="table table-bordered table-hover mb-0">

                    <thead class="table-dark">

                        <tr>

                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Precio</th>
                            <th>Fecha Vigencia</th>
                            <th>Activo</th>
                            <th width="220">Acciones</th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($fuels as $fuel)
                            <tr>

                                <td>{{ $fuel->id }}</td>

                                <td>
                                    {{ $fuel->name }}
                                </td>

                                <td>
                                    $ {{ number_format($fuel->price, 2, ',', '.') }}
                                </td>

                                <td>
                                    {{ $fuel->effective_date }}
                                </td>

                                <td>

                                    @if ($fuel->active)
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
                                    <x-action-buttons :showRoute="'fuel-prices.show'" :editRoute="'fuel-prices.edit'" :deleteRoute="'fuel-prices.destroy'"
                                        :id="$fuel" />
                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="6" class="text-center">

                                    No hay registros

                                </td>

                            </tr>
                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>
@endsection
