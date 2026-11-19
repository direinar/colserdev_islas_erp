@extends('layouts.app')

@section('title', 'Parámetros de liquidación')

@section('content')
    <div class="container">

        <x-page-header title="Parámetros de liquidación" :subtitle="'Vigente desde '.$parametro->vigente_desde->format('d/m/Y').($parametro->descripcion ? ' · '.$parametro->descripcion : '')" icon="sliders">
            <a href="{{ route('nomina.parametros-liquidacion.edit', $parametro) }}" class="btn btn-light">Editar</a>
            <a href="{{ route('nomina.parametros-liquidacion.index') }}" class="btn btn-light">Volver</a>
        </x-page-header>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="row">
            <div class="col-lg-6 mb-3">
                <div class="card h-100">
                    <div class="card-header fw-semibold">Devengados</div>
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Concepto</th>
                                    <th>Unidad</th>
                                    <th class="text-end">Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Ordinarias (salario básico ÷ {{ $parametro->horas_mensuales }})</td>
                                    <td>Hora</td>
                                    <td class="text-end">$ {{ number_format($parametro->valorHoraOrdinaria(), 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>Salario básico</td>
                                    <td>Mes</td>
                                    <td class="text-end">$ {{ number_format((float) $parametro->salario_minimo, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>Recargo nocturno ({{ (float) $parametro->porcentaje_recargo_nocturno }} %)</td>
                                    <td>Hora</td>
                                    <td class="text-end">$ {{ number_format($parametro->valorHoraRecargoNocturno(), 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>Recargo dominical y festivo ({{ (float) $parametro->porcentaje_recargo_festivo }} %)</td>
                                    <td>Hora</td>
                                    <td class="text-end">$ {{ number_format($parametro->valorHoraRecargoFestivo(), 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>Auxilio de transporte</td>
                                    <td>Mes</td>
                                    <td class="text-end">$ {{ number_format((float) $parametro->auxilio_transporte, 0, ',', '.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-3">
                <div class="card h-100">
                    <div class="card-header fw-semibold">Jornada y franjas</div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-6">Jornada semanal</dt>
                            <dd class="col-sm-6">{{ $parametro->horas_semanales }} horas</dd>
                            <dt class="col-sm-6">Horas mensuales (divisor)</dt>
                            <dd class="col-sm-6">{{ $parametro->horas_mensuales }}</dd>
                            <dt class="col-sm-6">Recargo nocturno</dt>
                            <dd class="col-sm-6">{{ substr($parametro->hora_inicio_nocturna, 0, 5) }} a {{ substr($parametro->hora_fin_nocturna, 0, 5) }}</dd>
                            <dt class="col-sm-6">Recargo dominical/festivo</dt>
                            <dd class="col-sm-6">{{ substr($parametro->hora_inicio_festiva, 0, 5) }} a {{ substr($parametro->hora_fin_festiva, 0, 5) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>

            @foreach (App\Models\Nomina\ParametroLiquidacion::gruposPorcentajes() as $grupo => $campos)
                <div class="col-lg-6 mb-3">
                    <div class="card h-100">
                        <div class="card-header fw-semibold">{{ $grupo }}</div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                @foreach ($campos as $campo => $etiqueta)
                                    <dt class="col-sm-8">{{ $etiqueta }}</dt>
                                    <dd class="col-sm-4 text-end">{{ (float) $parametro->{$campo} }} %</dd>
                                @endforeach
                            </dl>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

    </div>
@endsection
