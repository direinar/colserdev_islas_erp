@extends('layouts.app')

@section('title', 'Consolidación de prestaciones '.$anio)

@php
    $dinero = fn (float $valor): string => number_format($valor, 0, ',', '.');
    $suma = fn (string $campo): string => $dinero(array_sum(array_column($filas, $campo)));
@endphp

@section('content')
    @include('nomina.partials.estilos')

    <x-page-header :title="'Consolidación de prestaciones al 31 de diciembre de '.$anio" icon="safe"
        subtitle="Cesantías (promedio × días ÷ 360), intereses (12 % anual proporcional), vacaciones (promedio × días ÷ 720) y pagos del año.">
        <form method="GET" action="{{ route('nomina.prestaciones.consolidacion') }}" class="d-flex gap-2 align-items-end">
            <div>
                <label for="anio" class="form-label">AÑO</label>
                <input id="anio" type="number" name="anio" value="{{ $anio }}" min="2020" max="2100" class="form-control form-control-sm" style="width:6rem;" onchange="this.form.submit()">
            </div>
        </form>
        <button type="button" class="btn btn-outline-light btn-sm" onclick="window.print()"><i class="bi bi-printer" aria-hidden="true"></i> Imprimir</button>
    </x-page-header>

    @include('nomina.partials.errores-formulario')

    <div class="nm-kpis">
        <div class="nm-kpi nm-azul"><span class="nm-kpi-icono"><i class="bi bi-safe" aria-hidden="true"></i></span>
            <span><span class="nm-kpi-valor d-block">$ {{ $suma('cesantias') }}</span><span class="nm-kpi-titulo">Cesantías a consignar al fondo</span></span></div>
        <div class="nm-kpi nm-verde"><span class="nm-kpi-icono"><i class="bi bi-percent" aria-hidden="true"></i></span>
            <span><span class="nm-kpi-valor d-block">$ {{ $suma('intereses') }}</span><span class="nm-kpi-titulo">Intereses a pagar al trabajador</span></span></div>
        <div class="nm-kpi nm-amarillo"><span class="nm-kpi-icono"><i class="bi bi-sun" aria-hidden="true"></i></span>
            <span><span class="nm-kpi-valor d-block">$ {{ $suma('vacaciones') }}</span><span class="nm-kpi-titulo">Vacaciones causadas</span></span></div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-sm nm-tabla">
            <thead class="table-primary">
                <tr>
                    <th class="col-fija">TRABAJADOR</th>
                    <th class="num">DÍAS</th>
                    @foreach (range(1, 12) as $mes)
                        <th class="num">{{ mb_strtoupper(\Carbon\Carbon::create($anio, $mes, 1)->locale('es')->shortMonthName) }}</th>
                    @endforeach
                    <th class="num">PROMEDIO</th>
                    <th class="num">CESANTÍAS</th>
                    <th class="num">INTERESES</th>
                    <th class="num">VACACIONES</th>
                    <th class="num">PRIMA PAGADA</th>
                    <th class="num">SALUD</th>
                    <th class="num">PENSIÓN</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($filas as $fila)
                    <tr>
                        <td class="col-fija">{{ $fila['empleado']->nombre_completo }}
                            @if ($fila['sin_historial']) <span class="badge text-bg-info">SIN HISTORIAL</span> @endif
                        </td>
                        <td class="num">{{ $fila['dias'] }}</td>
                        @foreach (range(1, 12) as $mes)
                            <td class="num">{{ isset($fila['meses'][$mes]) ? $dinero($fila['meses'][$mes]) : '' }}</td>
                        @endforeach
                        <td class="num">{{ $dinero($fila['promedio']) }}</td>
                        <td class="num fw-bold">{{ $dinero($fila['cesantias']) }}</td>
                        <td class="num fw-bold">{{ $dinero($fila['intereses']) }}</td>
                        <td class="num">{{ $dinero($fila['vacaciones']) }}</td>
                        <td class="num">{{ $dinero($fila['prima_pagada']) }}</td>
                        <td class="num">{{ $dinero($fila['salud']) }}</td>
                        <td class="num">{{ $dinero($fila['pension']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="21" class="text-center text-muted py-4">No hay empleados vinculados en {{ $anio }}.</td></tr>
                @endforelse
            </tbody>
            @if ($filas)
                <tfoot>
                    <tr class="fila-total">
                        <td class="col-fija" colspan="15">TOTALES</td>
                        <td class="num">{{ $suma('cesantias') }}</td>
                        <td class="num">{{ $suma('intereses') }}</td>
                        <td class="num">{{ $suma('vacaciones') }}</td>
                        <td class="num">{{ $suma('prima_pagada') }}</td>
                        <td class="num">{{ $suma('salud') }}</td>
                        <td class="num">{{ $suma('pension') }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
    <p class="small text-muted">Las cesantías se consignan al fondo de cada trabajador antes del 14 de febrero; los intereses se pagan al trabajador en enero.
        Sin nóminas aprobadas en el año, la base es el sueldo básico más el auxilio de transporte.</p>

    @include('nomina.prestaciones.aplicar', [
        'concepto' => 'intereses_cesantias',
        'valores' => collect($filas)->mapWithKeys(fn (array $fila): array => [$fila['empleado']->id => $fila['intereses']])->all(),
        'texto' => 'Pagar los intereses de cesantías en la nómina de',
    ])
@endsection
