@extends('layouts.app')

@section('title', 'Conteo - Cuadre de efectivo')

@section('content')
    @include('conteos.partials.encabezado')

    @php
        $pesosInput = fn ($valor): string => (float) $valor != 0 ? number_format((float) $valor, 0, ',', '.') : '';
        $billetes = $conteo->billetes->isEmpty() ? collect([null]) : $conteo->billetes->values();
    @endphp

    <form method="POST" action="{{ route('conteos.guardar', [$conteo, 'efectivo']) }}">
        @csrf
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header fw-bold">BILLETES Y FAJOS</div>
                    <div class="card-body">
                        <p class="small text-muted mb-2">Se digita lo que suma cada denominación (10 billetes de 100 mil =
                            1.000.000). FAJOS: el valor total de los fajos.</p>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0 conteo-tabla" id="tabla-billetes">
                                <thead>
                                    <tr>
                                        <th>BILLETE</th>
                                        <th>VALOR</th>
                                        @if ($editable)
                                            <th class="text-center" style="width: 70px;">ACCIÓN</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($billetes as $i => $billete)
                                        <tr>
                                            <td>
                                                <select name="billetes[{{ $i }}][denominacion]" class="form-select form-select-sm" @disabled(! $editable)>
                                                    @foreach (\App\Models\Conteo::DENOMINACIONES as $denominacion)
                                                        <option value="{{ $denominacion }}" @selected($billete?->denominacion === $denominacion)>{{ $denominacion }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td><input type="text" name="billetes[{{ $i }}][valor]" class="form-control form-control-sm text-end"
                                                    inputmode="decimal" value="{{ $billete ? $pesosInput($billete->valor) : '' }}" @disabled(! $editable)></td>
                                            @if ($editable)
                                                <td class="text-center"><button type="button" class="btn btn-sm btn-danger quitar-billete" aria-label="Quitar fila">×</button></td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if ($editable)
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="agregar-billete">+ AGREGAR FILA</button>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header fw-bold">CUADRE DE EFECTIVO</div>
                    <div class="card-body">
                        <table class="table table-bordered table-sm mb-0 conteo-tabla">
                            <tbody>
                                <tr>
                                    <th>BILLETES</th>
                                    <td class="text-end">{{ number_format($resumen['billetes'], 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <th><label for="moneda" class="mb-0">MONEDA</label></th>
                                    <td><input type="text" name="moneda" id="moneda" class="form-control form-control-sm text-end"
                                            inputmode="decimal" value="{{ $pesosInput($conteo->moneda) }}" @disabled(! $editable)></td>
                                </tr>
                                <tr>
                                    <th>SUB TOTAL</th>
                                    <td class="text-end">{{ number_format($resumen['subtotal'], 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <th>RETIROS <span class="small fw-normal">(retiros antes de este cierre)</span></th>
                                    <td class="text-end">{{ number_format($resumen['retiros'], 0, ',', '.') }}</td>
                                </tr>
                                <tr class="total">
                                    <th>TOTAL CIERRE</th>
                                    <td class="text-end">{{ number_format($resumen['total_cierre'], 0, ',', '.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <p class="small text-muted mt-2 mb-0">Los totales se actualizan al GUARDAR.</p>
                    </div>
                </div>
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
                const cuerpo = document.querySelector('#tabla-billetes tbody');
                let siguiente = cuerpo.querySelectorAll('tr').length;

                document.getElementById('agregar-billete').addEventListener('click', function() {
                    const nueva = cuerpo.querySelector('tr').cloneNode(true);
                    nueva.querySelectorAll('input, select').forEach(campo => {
                        campo.name = campo.name.replace(/billetes\[\d+\]/, 'billetes[' + siguiente + ']');
                        if (campo.tagName === 'INPUT') {
                            campo.value = '';
                            delete campo.dataset.decInit;
                        }
                    });
                    siguiente++;
                    cuerpo.appendChild(nueva);
                    window.initDecimalFormatters?.();
                });

                cuerpo.addEventListener('click', function(event) {
                    const quitar = event.target.closest('.quitar-billete');
                    if (quitar && cuerpo.querySelectorAll('tr').length > 1) quitar.closest('tr').remove();
                });
            })();
        </script>
    @endif
@endsection
