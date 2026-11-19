@extends('layouts.app')

@section('title', 'Parámetros de liquidación')

@section('content')
    <div class="container">

        <x-page-header title="Parámetros de liquidación" icon="sliders"
            subtitle="Tabla de parámetros por vigencia: salario básico, auxilio de transporte, recargos, aportes y provisiones.">
            <a href="{{ route('nomina.parametros-liquidacion.create') }}" class="btn btn-light">Nueva vigencia</a>
        </x-page-header>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @unless ($vigente)
            <div class="alert alert-warning">No hay parámetros vigentes para hoy: cree una vigencia para poder liquidar.</div>
        @endunless

        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-primary">
                    <tr>
                        <th>Vigente desde</th>
                        <th>Descripción</th>
                        <th class="text-end">Salario básico</th>
                        <th class="text-end">Aux. transporte</th>
                        <th class="text-end">Valor hora</th>
                        <th class="text-end">Recargo nocturno</th>
                        <th class="text-end">Recargo festivo</th>
                        <th width="250">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($parametros as $parametro)
                        <tr>
                            <td>
                                {{ $parametro->vigente_desde->format('d/m/Y') }}
                                @if ($vigente?->is($parametro))
                                    <span class="badge text-bg-success">Vigente</span>
                                @endif
                            </td>
                            <td>{{ $parametro->descripcion ?: '—' }}</td>
                            <td class="text-end">$ {{ number_format((float) $parametro->salario_minimo, 0, ',', '.') }}</td>
                            <td class="text-end">$ {{ number_format((float) $parametro->auxilio_transporte, 0, ',', '.') }}</td>
                            <td class="text-end">$ {{ number_format($parametro->valorHoraOrdinaria(), 0, ',', '.') }}</td>
                            <td class="text-end">{{ (float) $parametro->porcentaje_recargo_nocturno }} %</td>
                            <td class="text-end">{{ (float) $parametro->porcentaje_recargo_festivo }} %</td>
                            <td>
                                <x-action-buttons :showRoute="'nomina.parametros-liquidacion.show'" :editRoute="'nomina.parametros-liquidacion.edit'" :deleteRoute="'nomina.parametros-liquidacion.destroy'" :id="$parametro" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">No hay vigencias registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
@endsection
