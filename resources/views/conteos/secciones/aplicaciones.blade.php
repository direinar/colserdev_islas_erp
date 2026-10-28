@extends('layouts.app')

@section('title', 'Conteo - Consignación en bancos')

@section('content')
    @include('conteos.partials.encabezado')

    @php
        $pesos = fn ($valor): string => number_format((float) $valor, 0, ',', '.');
        $pesosInput = fn ($valor): string => (float) $valor != 0 ? number_format((float) $valor, 0, ',', '.') : '';
        $filas = $conteo->aplicaciones->isEmpty() ? collect([null]) : $conteo->aplicaciones->values();
    @endphp

    <form method="POST" action="{{ route('conteos.guardar', [$conteo, 'aplicaciones']) }}">
        @csrf
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <strong>CONSIGNACIÓN EN BANCOS Y OTRAS APLICACIONES</strong>
                <a href="{{ route('conteos.catalogos') }}" class="btn btn-sm btn-outline-secondary">Destinos, bancos y terceros</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0 conteo-tabla" id="tabla-aplicaciones">
                        <thead>
                            <tr>
                                <th>DESTINO</th>
                                <th>BANCO / TERCERO</th>
                                <th>VALOR</th>
                                @if ($editable)
                                    <th class="text-center" style="width: 70px;">ACCIÓN</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($filas as $i => $fila)
                                <tr>
                                    <td>
                                        <select name="filas[{{ $i }}][conteo_destino_id]" class="form-select form-select-sm" @disabled(! $editable)>
                                            <option value="">Seleccione destino</option>
                                            @foreach ($destinos as $destino)
                                                <option value="{{ $destino->id }}" @selected($fila?->conteo_destino_id === $destino->id)>{{ $destino->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select name="filas[{{ $i }}][conteo_tercero_id]" class="form-select form-select-sm" @disabled(! $editable)>
                                            <option value="">Seleccione banco o tercero</option>
                                            @foreach ($terceros as $tercero)
                                                <option value="{{ $tercero->id }}" @selected($fila?->conteo_tercero_id === $tercero->id)>{{ $tercero->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="text" name="filas[{{ $i }}][valor]" class="form-control form-control-sm text-end"
                                            inputmode="decimal" value="{{ $fila ? $pesosInput($fila->valor) : '' }}" @disabled(! $editable)></td>
                                    @if ($editable)
                                        <td class="text-center"><button type="button" class="btn btn-sm btn-danger quitar-aplicacion" aria-label="Quitar fila">×</button></td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="total">
                                <td colspan="2" class="text-end">TOTAL CONSIGNACIONES Y OTROS</td>
                                <td class="text-end">{{ $pesos($resumen['aplicaciones']) }}</td>
                                @if ($editable)
                                    <td></td>
                                @endif
                            </tr>
                            <tr>
                                <td colspan="2" class="text-end">A CONSIGNAR <span class="small">(cuadre del conteo)</span></td>
                                <td class="text-end">{{ $pesos($resumen['a_consignar']) }}</td>
                                @if ($editable)
                                    <td></td>
                                @endif
                            </tr>
                            <tr class="total">
                                <td colspan="2" class="text-end">DIFERENCIA NO CONSIGNADA <span class="small fw-normal">(a consignar − total consignaciones)</span></td>
                                <td @class(['text-end', 'text-danger' => round($resumen['no_consignado']) < 0])>{{ $pesos($resumen['no_consignado']) }}</td>
                                @if ($editable)
                                    <td></td>
                                @endif
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @if ($editable)
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="agregar-aplicacion">+ AGREGAR FILA</button>
                @endif
            </div>
        </div>

        @if ($editable)
            <div class="text-end mt-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save" aria-hidden="true"></i> GUARDAR</button>
            </div>
        @endif
    </form>

    @if ($editable)
        <script>
            (function() {
                const cuerpo = document.querySelector('#tabla-aplicaciones tbody');
                let siguiente = cuerpo.querySelectorAll('tr').length;

                document.getElementById('agregar-aplicacion').addEventListener('click', function() {
                    const nueva = cuerpo.querySelector('tr').cloneNode(true);
                    nueva.querySelectorAll('input, select').forEach(campo => {
                        campo.name = campo.name.replace(/filas\[\d+\]/, 'filas[' + siguiente + ']');
                        campo.value = '';
                        delete campo.dataset.decInit;
                    });
                    siguiente++;
                    cuerpo.appendChild(nueva);
                    window.initDecimalFormatters?.();
                });

                cuerpo.addEventListener('click', function(event) {
                    const quitar = event.target.closest('.quitar-aplicacion');
                    if (!quitar) return;
                    if (cuerpo.querySelectorAll('tr').length > 1) {
                        quitar.closest('tr').remove();
                    } else {
                        quitar.closest('tr').querySelectorAll('input, select').forEach(campo => campo.value = '');
                    }
                });
            })();
        </script>
    @endif
@endsection
