@extends('layouts.app')

@section('content')
    <div class="container">

        <x-page-header title="Precios de combustible" icon="fuel-pump"
            subtitle="Precio de venta vigente de gasolina y ACPM según su fecha de vigencia.">
            <a href="{{ route('fuel-prices.create') }}" class="btn btn-light">
                <i class="bi bi-plus-circle" aria-hidden="true"></i> Nuevo registro
            </a>
        </x-page-header>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <div class="card shadow-sm">

            <div class="card-body p-0">

                <div class="table-responsive">
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

    </div>
@endsection
