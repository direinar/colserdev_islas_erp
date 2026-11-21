@extends('layouts.app')

@section('title', 'Conteo - Consignaciones internas')

@section('content')
    @include('conteos.partials.encabezado')

    @php
        $pesos = fn ($valor): string => number_format((float) $valor, 0, ',', '.');
        $consecutivo = $conteo->consignaciones->where('manual', false);
        $manuales = $conteo->consignaciones->where('manual', true)->values();
        $sinPlanilla = $consecutivo->whereNull('turno_id')->count();
        // Tras un error de validación se muestran las filas manuales digitadas.
        $filasManuales = old('manuales', $manuales->map(fn ($m) => [
            'numero' => $m->numero,
            'valor' => $m->valor !== null ? $pesos($m->valor) : '',
            'concepto' => $m->concepto,
        ])->all());
        $columnas = $editable ? 4 : 3;
    @endphp

    <form method="POST" action="{{ route('conteos.guardar', [$conteo, 'consignaciones']) }}" id="consignaciones-form">
        @csrf
        <div class="card mb-3">
            <div class="card-header fw-bold">DETALLE DE NUMERACIÓN DE CONSIGNACIONES DE COMBUSTIBLE</div>
            <div class="card-body">
                <p class="small text-muted mb-3">El primer número sigue al último del conteo anterior; se digita el
                    <strong>último No.</strong> de este conteo y el sistema genera los intermedios y trae el valor de cada uno
                    de las planillas de turno (CONSIGNACIONES). El último No. de este conteo es el punto de partida del próximo.</p>

                {{-- Cada No. al frente de su título, en un campo del ancho de 4 dígitos. --}}
                <style>
                    .conteo-numero { width: calc(4ch + 1.6rem); text-align: right; font-variant-numeric: tabular-nums; }
                    .conteo-numero::-webkit-outer-spin-button,
                    .conteo-numero::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
                    .conteo-numero[type="number"] { -moz-appearance: textfield; appearance: textfield; }
                </style>
                <div class="d-flex flex-wrap align-items-center gap-4">
                    <div class="d-flex align-items-center gap-2">
                        <label class="form-label fw-semibold mb-0" for="consignacion-anterior">ÚLTIMO No. DEL CONTEO ANTERIOR</label>
                        @if ($conteo->esPrimero())
                            {{-- Primer conteo: el punto de partida se digita (como un saldo inicial). --}}
                            <input type="number" name="consignacion_anterior" id="consignacion-anterior" min="0"
                                class="form-control conteo-numero" value="{{ $resumen['consignacion_anterior'] }}" @disabled(! $editable)>
                        @else
                            <input type="text" id="consignacion-anterior" class="form-control conteo-numero" readonly
                                value="{{ $resumen['consignacion_anterior'] }}">
                        @endif
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <label class="form-label fw-semibold mb-0" for="ultima-consignacion">ÚLTIMO No. DE ESTE CONTEO</label>
                        <input type="number" name="ultima_consignacion" id="ultima-consignacion" min="1" class="form-control conteo-numero"
                            value="{{ old('ultima_consignacion', $conteo->ultima_consignacion) }}" @disabled(! $editable) required>
                    </div>
                </div>

                {{-- Cambio de talonario: el consecutivo llega hasta el fin del talonario anterior y sigue desde el
                     primer No. del nuevo; los números intermedios no existen. --}}
                <div class="border rounded p-2 mt-3 bg-light">
                    <div class="fw-semibold small mb-2">
                        <i class="bi bi-journal-arrow-down" aria-hidden="true"></i> CAMBIO DE TALONARIO
                        <span class="fw-normal text-muted">(solo si en este conteo se empezó un talonario nuevo)</span>
                    </div>
                    <div class="row g-3 align-items-end">
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label small mb-0" for="talonario-fin">ÚLTIMO No. DEL TALONARIO ANTERIOR</label>
                            <input type="number" name="talonario_fin" id="talonario-fin" min="0" class="form-control"
                                value="{{ old('talonario_fin', $conteo->talonario_fin) }}" @disabled(! $editable)
                                placeholder="Ej.: 2499">
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label small mb-0" for="talonario-inicio">PRIMER No. DEL TALONARIO NUEVO</label>
                            <input type="number" name="talonario_inicio" id="talonario-inicio" min="1" class="form-control"
                                value="{{ old('talonario_inicio', $conteo->talonario_inicio) }}" @disabled(! $editable)
                                placeholder="Ej.: 2700">
                        </div>
                        <div class="col-lg-6 small text-muted">
                            Si el talonario anterior terminó justo en el último No. del conteo anterior, digite ese número
                            como último del talonario anterior.
                        </div>
                    </div>
                </div>

                @if ($editable)
                    <div class="mt-3 text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-arrow-repeat" aria-hidden="true"></i> GUARDAR Y TRAER VALORES
                        </button>
                    </div>
                @endif
            </div>
        </div>

        {{-- Números usados en planillas que se salieron del consecutivo (no entraron en ningún conteo). --}}
        @if ($fueraDeConsecutivo->isNotEmpty())
            <div class="alert alert-danger" id="fuera-de-consecutivo">
                <div class="fw-bold"><i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                    {{ $fueraDeConsecutivo->count() }} consignación(es) de planillas por fuera del consecutivo</div>
                <div class="small mb-1">Su No. ya debió contarse pero no está en ningún conteo (por ejemplo, un número del
                    talonario anterior después del cambio, o uno ya superado). Revise el No. en la planilla o agréguela
                    como registro manual.</div>
                <ul class="mb-0 small">
                    @foreach ($fueraDeConsecutivo as $fuera)
                        <li>No. {{ $fuera->consignacion_no }} — ${{ $pesos($fuera->valor) }} —
                            <a href="{{ route('turnos.create', ['turno_busqueda' => $fuera->turno->numero_turno]) }}">Turno #{{ $fuera->turno->numero_turno }}
                                ({{ $fuera->turno->fecha->format('d/m/Y') }})</a></li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($sinPlanilla > 0)
            <div class="alert alert-warning">
                {{ $sinPlanilla }} número(s) no están en ninguna planilla de turno (en rojo, con valor 0). Revise que la
                consignación tenga su No. en la planilla y vuelva a GUARDAR Y TRAER VALORES.
            </div>
        @endif

        <div class="card">
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0 conteo-tabla" id="tabla-consignaciones">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>VALOR</th>
                            <th>PLANILLA DE TURNO / CONCEPTO</th>
                            @if ($editable)
                                <th></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="table-warning">
                            <td>{{ $resumen['consignacion_anterior'] }}</td>
                            <td colspan="{{ $columnas - 1 }}" class="small">Último No. del conteo anterior</td>
                        </tr>
                        @foreach ($consecutivo as $consignacion)
                            @if ($conteo->talonario_inicio && $consignacion->numero === $conteo->talonario_inicio)
                                <tr class="table-info">
                                    <td colspan="{{ $columnas }}" class="small fw-semibold">
                                        <i class="bi bi-journal-arrow-down" aria-hidden="true"></i>
                                        Cambio de talonario: el anterior terminó en {{ $conteo->talonario_fin }}; el nuevo inicia en {{ $conteo->talonario_inicio }}.
                                    </td>
                                </tr>
                            @endif
                            <tr @class(['table-danger' => ! $consignacion->turno_id])>
                                <td>{{ $consignacion->numero }}</td>
                                <td class="text-end">{{ $pesos($consignacion->valor) }}</td>
                                <td @if ($editable) colspan="2" @endif>
                                    @if ($consignacion->turno)
                                        <a href="{{ route('turnos.create', ['turno_busqueda' => $consignacion->turno->numero_turno]) }}">
                                            Turno #{{ $consignacion->turno->numero_turno }} — {{ $consignacion->turno->fecha->format('d/m/Y') }}
                                        </a>
                                    @else
                                        <span class="text-danger fw-semibold">No está en ninguna planilla</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                    {{-- Filas manuales: fuera del consecutivo, con concepto; suman al total. --}}
                    <tbody id="consignaciones-manuales">
                        <tr class="table-secondary">
                            <td colspan="{{ $columnas }}" class="small fw-semibold">REGISTROS MANUALES (no consecutivos)</td>
                        </tr>
                        @if ($editable)
                            @foreach ($filasManuales ?: [['numero' => '', 'valor' => '', 'concepto' => '']] as $i => $fila)
                                <tr class="fila-manual">
                                    <td><input type="number" min="0" name="manuales[{{ $i }}][numero]" class="form-control form-control-sm"
                                            value="{{ $fila['numero'] ?? '' }}" placeholder="No. (opcional)"></td>
                                    <td><input type="text" inputmode="numeric" name="manuales[{{ $i }}][valor]"
                                            class="form-control form-control-sm text-end" value="{{ $fila['valor'] ?? '' }}"></td>
                                    <td><input type="text" maxlength="200" name="manuales[{{ $i }}][concepto]" class="form-control form-control-sm"
                                            value="{{ $fila['concepto'] ?? '' }}" placeholder="Concepto"></td>
                                    <td class="text-center"><button type="button" class="btn btn-sm btn-danger quitar-manual" title="Quitar fila">×</button></td>
                                </tr>
                            @endforeach
                        @else
                            @forelse ($manuales as $manual)
                                <tr>
                                    <td>{{ $manual->numero }}</td>
                                    <td class="text-end">{{ $pesos($manual->valor) }}</td>
                                    <td>{{ $manual->concepto }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="small text-muted">Sin registros manuales.</td>
                                </tr>
                            @endforelse
                        @endif
                    </tbody>
                    <tfoot>
                        @if ($editable)
                            <tr>
                                <td colspan="{{ $columnas }}">
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="agregar-manual">+ AGREGAR FILA MANUAL</button>
                                    <span class="small text-muted ms-2">Se suma al guardar con GUARDAR Y TRAER VALORES.</span>
                                </td>
                            </tr>
                        @endif
                        <tr class="total">
                            <td>TOTAL CONSIGNACIONES INTERNAS</td>
                            <td class="text-end">{{ $pesos($resumen['total_consignaciones']) }}</td>
                            <td @if ($editable) colspan="2" @endif></td>
                        </tr>
                        <tr>
                            <td>TOTAL CIERRE <span class="small">(cuadre de efectivo)</span></td>
                            <td class="text-end">{{ $pesos($resumen['total_cierre']) }}</td>
                            <td @if ($editable) colspan="2" @endif></td>
                        </tr>
                        <tr class="total">
                            <td>DIFERENCIA <span class="small fw-normal">(total cierre − consignaciones internas)</span></td>
                            <td @class(['text-end', 'text-danger' => round($resumen['diferencia']) < 0])>{{ $pesos($resumen['diferencia']) }}</td>
                            <td @if ($editable) colspan="2" @endif></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </form>

    @if ($editable)
        <script>
            (function() {
                const cuerpo = document.getElementById('consignaciones-manuales');
                let siguiente = cuerpo.querySelectorAll('.fila-manual').length;

                document.getElementById('agregar-manual').addEventListener('click', function() {
                    const modelo = cuerpo.querySelector('.fila-manual');
                    const fila = modelo.cloneNode(true);
                    fila.querySelectorAll('input').forEach(function(input) {
                        input.value = '';
                        input.name = input.name.replace(/manuales\[\d+\]/, 'manuales[' + siguiente + ']');
                    });
                    siguiente++;
                    cuerpo.appendChild(fila);
                    fila.querySelector('input').focus();
                });

                cuerpo.addEventListener('click', function(event) {
                    const boton = event.target.closest('.quitar-manual');
                    if (!boton) return;
                    const fila = boton.closest('.fila-manual');
                    // Siempre queda una fila para digitar: la última solo se vacía.
                    if (cuerpo.querySelectorAll('.fila-manual').length > 1) {
                        fila.remove();
                    } else {
                        fila.querySelectorAll('input').forEach(input => input.value = '');
                    }
                });
            })();
        </script>
    @endif
@endsection
