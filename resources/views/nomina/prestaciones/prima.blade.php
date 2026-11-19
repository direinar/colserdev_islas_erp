@extends('layouts.app')

@section('title', 'Prima semestral')

@php
    $dinero = fn (float $valor): string => number_format($valor, 0, ',', '.');
    $mesDesde = $semestre === 1 ? 1 : 7;
    $mesesSemestre = range($mesDesde, $mesDesde + 5);
@endphp

@section('content')
    @include('nomina.partials.estilos')

    <x-page-header :title="'Prima semestral · '.($semestre === 1 ? 'Primer' : 'Segundo').' semestre '.$anio" icon="gift"
        subtitle="Liquidador masivo: promedio de los ingresos del semestre (básico, recargos y auxilio) × días trabajados ÷ 360.">
        <form method="GET" action="{{ route('nomina.prestaciones.prima') }}" class="d-flex gap-2 align-items-end">
            <div>
                <label for="semestre" class="form-label">SEMESTRE</label>
                <select id="semestre" name="semestre" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="1" @selected($semestre === 1)>Enero - junio</option>
                    <option value="2" @selected($semestre === 2)>Julio - diciembre</option>
                </select>
            </div>
            <div>
                <label for="anio" class="form-label">AÑO</label>
                <input id="anio" type="number" name="anio" value="{{ $anio }}" min="2020" max="2100" class="form-control form-control-sm" style="width:6rem;" onchange="this.form.submit()">
            </div>
        </form>
        <button type="button" class="btn btn-outline-light btn-sm" onclick="window.print()"><i class="bi bi-printer" aria-hidden="true"></i> Imprimir</button>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @include('nomina.partials.errores-formulario')

    @if (collect($filas)->contains('sin_historial', true))
        <div class="alert alert-info small">
            <i class="bi bi-info-circle" aria-hidden="true"></i> Los empleados marcados con <span class="badge text-bg-info">SIN HISTORIAL</span> no tienen nóminas aprobadas en el semestre:
            su base es el sueldo básico más el auxilio de transporte vigente.
        </div>
    @endif

    <div class="table-responsive">
        <table class="table table-bordered table-sm nm-tabla">
            <thead class="table-primary">
                <tr>
                    <th class="col-fija">EMPLEADO</th>
                    <th>FECHA INGRESO</th>
                    <th class="num">DÍAS</th>
                    @foreach ($mesesSemestre as $mes)
                        <th class="num">{{ mb_strtoupper(\Carbon\Carbon::create($anio, $mes, 1)->locale('es')->shortMonthName) }}</th>
                    @endforeach
                    <th class="num">TOTAL</th>
                    <th class="num">PROMEDIO</th>
                    <th class="num">PRIMA</th>
                    <th class="num">YA PAGADA</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($filas as $fila)
                    <tr>
                        <td class="col-fija">{{ $fila['empleado']->nombre_completo }}
                            @if ($fila['sin_historial']) <span class="badge text-bg-info">SIN HISTORIAL</span> @endif
                        </td>
                        <td>{{ $fila['fecha_ingreso']?->format('d/m/Y') }}</td>
                        <td class="num">{{ $fila['dias'] }}</td>
                        @foreach ($mesesSemestre as $mes)
                            <td class="num">{{ isset($fila['meses'][$mes]) ? $dinero($fila['meses'][$mes]) : '' }}</td>
                        @endforeach
                        <td class="num">{{ $dinero($fila['total']) }}</td>
                        <td class="num">{{ $dinero($fila['promedio']) }}</td>
                        <td class="num fw-bold">{{ $dinero($fila['prima']) }}</td>
                        <td class="num {{ $fila['ya_pagada'] > 0 ? 'text-success fw-semibold' : 'text-muted' }}">{{ $fila['ya_pagada'] > 0 ? $dinero($fila['ya_pagada']) : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="13" class="text-center text-muted py-4">No hay empleados vinculados en el semestre.</td></tr>
                @endforelse
            </tbody>
            @if ($filas)
                <tfoot>
                    <tr class="fila-total">
                        <td class="col-fija" colspan="{{ 3 + count($mesesSemestre) + 2 }}">TOTAL PRIMA DEL SEMESTRE</td>
                        <td class="num">{{ $dinero(array_sum(array_column($filas, 'prima'))) }}</td>
                        <td class="num">{{ $dinero(array_sum(array_column($filas, 'ya_pagada'))) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    @include('nomina.prestaciones.aplicar', [
        'concepto' => 'prima',
        'valores' => collect($filas)->mapWithKeys(fn (array $fila): array => [$fila['empleado']->id => $fila['prima']])->all(),
        'texto' => 'Pagar la prima en la nómina de',
    ])
@endsection
