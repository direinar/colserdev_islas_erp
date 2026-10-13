@extends('layouts.app')

@section('content')
    <div class="container">

        <x-page-header title="Precios de combustible" icon="fuel-pump"
            subtitle="Precio de venta de gasolina y ACPM según el número de turno desde el que rige.">
            <a href="{{ route('fuel-prices.create') }}" class="btn btn-light">
                <i class="bi bi-plus-circle" aria-hidden="true"></i> Nuevo registro
            </a>
        </x-page-header>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
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
                            <th>Rige desde el turno</th>
                            <th>Fecha del cambio</th>
                            <th>Planillas</th>
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
                                    {{ $fuel->turno_desde ? str_pad($fuel->turno_desde, 3, '0', STR_PAD_LEFT) : '—' }}
                                </td>

                                <td>
                                    {{ $fuel->effective_date }}
                                </td>

                                <td>

                                    {{-- Un precio que ya liquidó planillas no se edita ni se elimina. --}}
                                    @if ($usados->contains($fuel->id))
                                        <span class="badge bg-secondary">
                                            EN USO
                                        </span>
                                    @else
                                        <span class="badge bg-success">
                                            SIN PLANILLAS
                                        </span>
                                    @endif

                                </td>

                                <td>
                                    @if ($usados->contains($fuel->id))
                                        <x-action-buttons :showRoute="'fuel-prices.show'" :id="$fuel" />
                                    @else
                                        <x-action-buttons :showRoute="'fuel-prices.show'" :editRoute="'fuel-prices.edit'" :deleteRoute="'fuel-prices.destroy'"
                                            :id="$fuel" />
                                    @endif
                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="7" class="text-center">

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
