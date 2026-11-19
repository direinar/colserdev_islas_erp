@extends('layouts.app')

@section('title', 'Acumulados de nómina '.$anio)

@php
    $dinero = fn (float $valor): string => number_format($valor, 0, ',', '.');
    $columnas = [
        ['SALARIOS', null], ['Sueldo', 'sueldo'], ['Sobresueldos', 'sobresueldos'], ['Auxilio de transporte', 'auxilio'], ['TOTAL SALARIOS', 'total_salarios'],
        ['CESANTÍAS E INTERESES', null], ['Cesantías', 'cesantias'], ['Intereses s/cesantías', 'intereses'], ['TOTAL CESANTÍAS E INTERESES', 'total_cesantias'],
        ['PRESTACIONES SOCIALES', null], ['Vacaciones', 'vacaciones'], ['Prima legal', 'prima'], ['TOTAL PRESTACIONES SOCIALES', 'total_prestaciones'],
        ['TOTAL INGRESOS LABORALES', 'total_ingresos'],
        ['DESCUENTOS', null], ['Salud', 'salud'], ['Pensión', 'pension'], ['TOTAL DESCUENTOS', 'total_descuentos'],
    ];
@endphp

@section('content')
    @include('nomina.partials.estilos')

    <x-page-header :title="'Acumulados de nómina · '.$anio" icon="collection"
        subtitle="Suma de las nóminas APROBADAS del año por empleado (hoja ACUMULADORES). De aquí sale el certificado de ingresos.">
        <form method="GET" action="{{ route('nomina.acumulados.index') }}" class="d-flex gap-2 align-items-end">
            <div>
                <label for="anio" class="form-label">AÑO</label>
                <select id="anio" name="anio" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach ($anios as $opcion)
                        <option value="{{ $opcion }}" @selected($opcion === $anio)>{{ $opcion }}</option>
                    @endforeach
                </select>
            </div>
        </form>
        <button type="button" class="btn btn-outline-light btn-sm" onclick="window.print()"><i class="bi bi-printer" aria-hidden="true"></i> Imprimir</button>
    </x-page-header>

    <p class="small text-muted">
        Meses aprobados en {{ $anio }}:
        @forelse ($mesesAprobados as $mes)
            <span class="badge text-bg-success">{{ ucfirst(\Carbon\Carbon::create($anio, $mes, 1)->locale('es')->monthName) }}</span>
        @empty
            <span class="text-warning-emphasis">ninguno todavía. Apruebe las nóminas del año para verlas aquí.</span>
        @endforelse
    </p>

    @if ($acumulados)
        <div class="table-responsive">
            <table class="table table-bordered table-sm nm-tabla">
                <thead class="table-primary">
                    <tr>
                        <th class="col-fija">CONCEPTO</th>
                        @foreach ($acumulados as $fila)
                            <th class="num">{{ $fila['empleado']->nombreParaNomina() }}<div class="fw-normal small">{{ $fila['meses'] }} mes(es)</div></th>
                        @endforeach
                        <th class="num">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($columnas as [$etiqueta, $campo])
                        @if ($campo === null)
                            <tr class="fila-seccion"><td colspan="{{ count($acumulados) + 2 }}">{{ $etiqueta }}</td></tr>
                        @else
                            <tr @class(['fila-subtotal' => str_starts_with($etiqueta, 'TOTAL') && $campo !== 'total_ingresos', 'fila-total' => $campo === 'total_ingresos'])>
                                <td class="col-fija">{{ $etiqueta }}</td>
                                @foreach ($acumulados as $fila)
                                    <td class="num">{{ $dinero($fila[$campo]) }}</td>
                                @endforeach
                                <td class="num">{{ $dinero(array_sum(array_column($acumulados, $campo))) }}</td>
                            </tr>
                        @endif
                    @endforeach
                    <tr class="d-print-none">
                        <td class="col-fija fw-semibold">CERTIFICADO DE INGRESOS</td>
                        @foreach ($acumulados as $fila)
                            <td class="text-center text-nowrap">
                                <a href="{{ route('nomina.acumulados.certificado', [$fila['empleado'], 'anio' => $anio]) }}" class="btn btn-sm btn-outline-primary" target="_blank" title="Ver e imprimir"><i class="bi bi-eye" aria-hidden="true"></i></a>
                                <a href="{{ route('nomina.acumulados.certificado', [$fila['empleado'], 'anio' => $anio, 'pdf' => 1]) }}" class="btn btn-sm btn-outline-danger" title="PDF"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i></a>
                            </td>
                        @endforeach
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
    @else
        <div class="text-center text-muted py-5 border rounded bg-white">
            <i class="bi bi-inbox fs-2 d-block" aria-hidden="true"></i>
            No hay nóminas aprobadas en {{ $anio }}.
        </div>
    @endif
@endsection
