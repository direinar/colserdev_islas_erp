@extends('layouts.app')

@section('title', 'Programar turnos '.$planilla->nombreDelMes())

@section('content')
    @include('nomina.planillas-turnos.estilos')

    <div class="container-fluid">

        <x-page-header :title="'Programar turnos · '.$planilla->nombreDelMes()" icon="calendar-week"
            subtitle="Seleccione el turno de cada empleado por día; deje «—» en los días de descanso. Las horas se calculan al guardar.">
            <a href="{{ route('nomina.planillas-turnos.show', $planilla) }}" class="btn btn-light">Ver turnos</a>
            <a href="{{ route('nomina.planillas-turnos.liquidacion', $planilla) }}" class="btn btn-light">Recargos</a>
            <a href="{{ route('nomina.planillas-turnos.index') }}" class="btn btn-light">Volver</a>
        </x-page-header>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @include('nomina.partials.errores-formulario')

        @if ($empleados->isEmpty())
            <div class="alert alert-warning">
                No hay empleados con «Sobresueldos y recargos» vinculados en este mes.
                <a href="{{ route('nomina.empleados.index') }}">Revise los empleados</a>.
            </div>
        @elseif ($turnos->isEmpty())
            <div class="alert alert-warning">
                No hay turnos activos. <a href="{{ route('nomina.turnos.create') }}">Cree los turnos</a> antes de programar.
            </div>
        @else
            <form method="POST" action="{{ route('nomina.planillas-turnos.update', $planilla) }}">
                @csrf
                @method('PUT')

                @foreach ($semanas as $indice => $semana)
                    <div class="d-flex justify-content-between align-items-center mt-3 mb-1">
                        <h2 class="h6 mb-0">Semana {{ $indice + 1 }}: {{ $semana[0]->format('d/m') }} al {{ $semana[6]->format('d/m') }}</h2>
                        @if ($indice > 0)
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-copiar-semana="{{ $indice }}">
                                Copiar semana anterior
                            </button>
                        @endif
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm planilla-semana mb-2" data-semana="{{ $indice }}">
                            <thead class="table-light">
                                <tr>
                                    <th class="col-empleado">Empleado</th>
                                    @foreach ($semana as $dia)
                                        @php($enMes = $dia->month === $planilla->mes)
                                        <th class="{{ ! $enMes ? 'dia-fuera' : ($dia->isSunday() || in_array($dia->toDateString(), $festivos, true) ? 'dia-festivo' : '') }}">
                                            {{ mb_strtoupper($dia->locale('es')->minDayName) }} {{ $dia->day }}
                                        </th>
                                    @endforeach
                                    <th>Horas</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($empleados as $empleado)
                                    <tr data-empleado="{{ $empleado->id }}">
                                        <td class="col-empleado">{{ $empleado->nombre_corto }}</td>
                                        @foreach ($semana as $posicion => $dia)
                                            @if ($dia->month === $planilla->mes)
                                                @php($fecha = $dia->toDateString())
                                                @php($actual = old("turnos.{$empleado->id}.{$fecha}", $resumen[$empleado->id]['dias'][$fecha]->turno_id ?? ''))
                                                <td data-posicion="{{ $posicion }}">
                                                    <select name="turnos[{{ $empleado->id }}][{{ $fecha }}]" class="form-select form-select-sm celda-turno"
                                                        aria-label="Turno de {{ $empleado->nombre_corto }} el {{ $dia->format('d/m') }}">
                                                        <option value="">—</option>
                                                        @foreach ($turnos as $turno)
                                                            <option value="{{ $turno->id }}" data-color="{{ $turno->color }}" @selected((string) $actual === (string) $turno->id)>
                                                                {{ $turno->nombre }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                            @else
                                                <td class="dia-fuera" data-posicion="{{ $posicion }}"></td>
                                            @endif
                                        @endforeach
                                        <td>{{ (float) $resumen[$empleado->id]['semanas'][$indice] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach

                <div class="mb-3">
                    <label for="campo-observaciones" class="form-label">Observaciones</label>
                    <textarea id="campo-observaciones" name="observaciones" rows="2" class="form-control">{{ old('observaciones', $planilla->observaciones) }}</textarea>
                </div>

                <div class="d-flex gap-2 mb-4">
                    <button type="submit" class="btn btn-primary">Guardar turnos</button>
                    <span class="small text-muted align-self-center">La columna «Horas» muestra lo guardado; se actualiza al guardar.</span>
                </div>
            </form>
        @endif

    </div>

    <script>
        (() => {
            const pintar = (select) => {
                select.style.backgroundColor = select.selectedOptions[0]?.dataset.color || '';
            };

            document.querySelectorAll('select.celda-turno').forEach((select) => {
                pintar(select);
                select.addEventListener('change', () => pintar(select));
            });

            document.querySelectorAll('[data-copiar-semana]').forEach((boton) => {
                boton.addEventListener('click', () => {
                    const indice = Number(boton.dataset.copiarSemana);
                    const anterior = document.querySelector(`table[data-semana="${indice - 1}"]`);
                    const actual = document.querySelector(`table[data-semana="${indice}"]`);

                    actual.querySelectorAll('tr[data-empleado]').forEach((fila) => {
                        const filaAnterior = anterior.querySelector(`tr[data-empleado="${fila.dataset.empleado}"]`);

                        fila.querySelectorAll('td[data-posicion] select').forEach((select) => {
                            const posicion = select.closest('td').dataset.posicion;
                            const origen = filaAnterior?.querySelector(`td[data-posicion="${posicion}"] select`);

                            if (origen) {
                                select.value = origen.value;
                                pintar(select);
                            }
                        });
                    });
                });
            });
        })();
    </script>
@endsection
