@extends('layouts.app')

@section('title', 'Cartera')

@php
    $money = fn ($value) => number_format((float) $value, 0, ',', '.');
    // Campos editables: en blanco cuando el valor es cero para no ensuciar la tabla.
    $moneyInput = fn ($value) => (float) $value != 0 ? number_format((float) $value, 0, ',', '.') : '';
    $galonesInput = fn ($value) => (float) $value != 0 ? rtrim(rtrim(number_format((float) $value, 3, ',', '.'), '0'), ',') : '';

    $totalCargos = $movimientos->sum(fn ($m) => (float) $m->vr_neto_cargo);
    $totalAbonos = $movimientos->sum(fn ($m) => (float) $m->abonos);
@endphp

@section('content')
    <div class="pastel-section mb-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
            <div>
                <h4 class="mb-0 text-danger fw-bold">CARTERA</h4>
                <small class="text-muted">Movimientos del cliente: créditos directos de las planillas de turno y registros manuales.</small>
            </div>
            @if ($customer)
                <button type="submit" form="cartera-form" class="btn btn-primary btn-sm">Guardar cartera</button>
            @endif
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <x-erp-card title="CARTERA (INFORME)">
        <form method="GET" action="{{ route('cartera.index') }}" id="cartera-filtro" class="p-3 pb-2">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small mb-1" for="customer-id">Cliente</label>
                    <select name="customer_id" id="customer-id" class="form-select form-select-sm"
                        onchange="this.form.submit()">
                        <option value="">Seleccione cliente</option>
                        @foreach ($customers as $item)
                            <option value="{{ $item->id }}" @selected($customer?->id === $item->id)>
                                {{ $item->name }} - {{ $item->document }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1" for="fecha-inicial">Fecha inicial</label>
                    <input type="date" name="fecha_inicial" id="fecha-inicial" class="form-control form-control-sm"
                        value="{{ $fechaInicial }}" onchange="this.form.submit()">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1" for="fecha-final">Fecha final</label>
                    <input type="date" name="fecha_final" id="fecha-final" class="form-control form-control-sm"
                        value="{{ $fechaFinal }}" onchange="this.form.submit()">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1" for="saldo-anterior">Saldo anterior</label>
                    <input type="text" id="saldo-anterior" class="form-control form-control-sm text-end" readonly
                        value="{{ $money($saldoAnterior) }}" data-value="{{ $saldoAnterior }}"
                        title="Saldo acumulado de los movimientos anteriores a la fecha inicial">
                </div>
            </div>
        </form>

        @if (! $customer)
            <div class="p-3 pt-2 text-muted">
                Seleccione un cliente para ver sus movimientos de cartera.
            </div>
        @else
            <div class="px-3 pb-2 d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-2">
                <div>
                    <div class="small text-muted">Cliente</div>
                    <h5 class="mb-0 fw-bold">{{ $customer->name }}</h5>
                    <div class="small text-muted">NIT / Documento: {{ $customer->document }}</div>
                </div>
                <div class="text-md-end">
                    <button type="button" id="add-cartera-row" class="btn btn-sm btn-outline-primary">+ Agregar fila</button>
                    <div class="small text-muted mt-1">
                        <span class="badge text-bg-secondary">Planilla</span>
                        Movimiento de una planilla de turno: planilla, fecha, factura y valor se modifican desde el turno.
                    </div>
                </div>
            </div>

            <form id="cartera-form" method="POST" action="{{ route('cartera.store') }}">
                @csrf
                <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                <input type="hidden" name="fecha_inicial" value="{{ $fechaInicial }}">
                <input type="hidden" name="fecha_final" value="{{ $fechaFinal }}">
                <div id="cartera-eliminar"></div>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0" id="cartera-table">
                        <thead style="background-color:#ccccff;">
                            <tr>
                                <th class="text-center align-middle">PLANILLAS</th>
                                <th class="text-center align-middle">FECHA</th>
                                <th class="text-center align-middle">FACTURA</th>
                                <th class="text-center align-middle">PLACAS</th>
                                <th class="text-center align-middle">PRODUCTO</th>
                                <th class="text-center align-middle">GALONES</th>
                                <th class="text-center align-middle">VR. UNITARIO</th>
                                <th class="text-center align-middle">BRUTO</th>
                                <th class="text-center align-middle">DESCUENTO</th>
                                <th class="text-center align-middle">CUENTA</th>
                                <th class="text-center align-middle">CONCEPTO</th>
                                <th class="text-center align-middle">TERCERO</th>
                                <th class="text-center align-middle">NIT</th>
                                <th class="text-center align-middle">VR. NETO CARGO</th>
                                <th class="text-center align-middle">ABONOS</th>
                                <th class="text-center align-middle">SALDO</th>
                                <th class="text-center align-middle">ACCION</th>
                            </tr>
                        </thead>

                        <tbody id="cartera-body">
                            @foreach ($movimientos as $index => $m)
                                @php $deTurno = $m->esDeTurno(); @endphp
                                <tr data-index="{{ $index }}" data-origen="{{ $deTurno ? 'turno' : 'manual' }}"
                                    data-id="{{ $m->id }}">
                                    <td class="text-nowrap">
                                        <input type="hidden" name="detalles[{{ $index }}][id]" value="{{ $m->id }}">
                                        @if ($deTurno)
                                            <a href="{{ route('turnos.create', ['turno_busqueda' => $m->planillas]) }}"
                                                class="text-decoration-none" title="Abrir planilla del turno">
                                                <span class="badge text-bg-secondary">Planilla</span>
                                                {{ $m->planillas }}
                                            </a>
                                        @else
                                            <input type="text" name="detalles[{{ $index }}][planillas]"
                                                class="form-control form-control-sm" value="{{ $m->planillas }}">
                                        @endif
                                    </td>
                                    <td>
                                        <input type="date" name="detalles[{{ $index }}][fecha]"
                                            class="form-control form-control-sm" value="{{ $m->fecha?->toDateString() }}"
                                            @readonly($deTurno)>
                                    </td>
                                    <td>
                                        <input type="text" name="detalles[{{ $index }}][factura]"
                                            class="form-control form-control-sm" value="{{ $m->factura }}"
                                            @readonly($deTurno)>
                                    </td>
                                    <td>
                                        <input type="text" name="detalles[{{ $index }}][placas]"
                                            class="form-control form-control-sm" value="{{ $m->placas }}">
                                    </td>
                                    <td>
                                        <input type="text" name="detalles[{{ $index }}][producto]"
                                            class="form-control form-control-sm" value="{{ $m->producto }}">
                                    </td>
                                    <td>
                                        <input type="text" name="detalles[{{ $index }}][galones]"
                                            class="form-control form-control-sm text-end galones-input" inputmode="decimal"
                                            value="{{ $galonesInput($m->galones) }}">
                                    </td>
                                    <td>
                                        <input type="text" name="detalles[{{ $index }}][vr_unitario]"
                                            class="form-control form-control-sm text-end vr-unitario-input money-input"
                                            inputmode="decimal" value="{{ $moneyInput($m->vr_unitario) }}">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm text-end bruto-input" readonly
                                            value="{{ $money($m->bruto) }}">
                                    </td>
                                    <td>
                                        <input type="text" name="detalles[{{ $index }}][descuento]"
                                            class="form-control form-control-sm text-end descuento-input money-input"
                                            inputmode="decimal" value="{{ $moneyInput($m->descuento) }}"
                                            @readonly($deTurno)>
                                    </td>
                                    <td>
                                        <input type="text" name="detalles[{{ $index }}][cuenta]"
                                            class="form-control form-control-sm" value="{{ $m->cuenta }}">
                                    </td>
                                    <td>
                                        <input type="text" name="detalles[{{ $index }}][concepto]"
                                            class="form-control form-control-sm" value="{{ $m->concepto }}">
                                    </td>
                                    <td>
                                        <input type="text" name="detalles[{{ $index }}][tercero]"
                                            class="form-control form-control-sm" value="{{ $m->tercero }}">
                                    </td>
                                    <td>
                                        <input type="text" name="detalles[{{ $index }}][nit]"
                                            class="form-control form-control-sm" value="{{ $m->nit }}">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm text-end vr-neto-cargo-input"
                                            readonly value="{{ $money($m->vr_neto_cargo) }}">
                                    </td>
                                    <td>
                                        <input type="text" name="detalles[{{ $index }}][abonos]"
                                            class="form-control form-control-sm text-end abonos-input money-input"
                                            inputmode="decimal" value="{{ $moneyInput($m->abonos) }}">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm text-end saldo-input" readonly>
                                    </td>
                                    <td class="text-center">
                                        @if ($deTurno)
                                            <i class="bi bi-lock text-muted" title="Se elimina desde la planilla del turno"></i>
                                        @else
                                            <button type="button" class="btn btn-sm btn-danger remove-row">×</button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                        <tfoot class="table-light fw-bold">
                            <tr id="cartera-sin-movimientos" @class(['d-none' => $movimientos->isNotEmpty()])>
                                <td colspan="17" class="text-center text-muted fw-normal">
                                    El cliente no tiene movimientos entre las fechas seleccionadas.
                                </td>
                            </tr>
                            <tr>
                                <td colspan="13" class="text-end">TOTALES</td>
                                <td class="text-end" id="total-cargos">{{ $money($totalCargos) }}</td>
                                <td class="text-end" id="total-abonos">{{ $money($totalAbonos) }}</td>
                                <td class="text-end" id="saldo-final">{{ $money($saldoAnterior + $totalCargos - $totalAbonos) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </form>
        @endif
    </x-erp-card>

    @if ($customer)
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const tbody = document.getElementById('cartera-body');
                const addBtn = document.getElementById('add-cartera-row');
                const eliminarContainer = document.getElementById('cartera-eliminar');
                const sinMovimientos = document.getElementById('cartera-sin-movimientos');
                const saldoAnterior = Number(document.getElementById('saldo-anterior').dataset.value) || 0;
                let nextIndex = tbody.querySelectorAll('tr').length;

                // Formato es-CO: punto = miles, coma = decimales.
                function parseNumber(value) {
                    if (!value) {
                        return 0;
                    }

                    const clean = value.toString().replace(/\./g, '').replace(/,/g, '.');
                    return Number(clean) || 0;
                }

                function formatNumber(number, decimals = 0) {
                    return Number(number).toLocaleString('es-CO', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: decimals,
                    });
                }

                function updateRow(row, saldoPrevio) {
                    const brutoInput = row.querySelector('.bruto-input');
                    const vrNetoCargoInput = row.querySelector('.vr-neto-cargo-input');
                    let cargo;

                    if (row.dataset.origen === 'turno') {
                        // El valor lo fija la planilla del turno.
                        cargo = parseNumber(vrNetoCargoInput.value);
                    } else {
                        const galones = parseNumber(row.querySelector('.galones-input').value);
                        const vrUnitario = parseNumber(row.querySelector('.vr-unitario-input').value);
                        const descuento = Math.round(parseNumber(row.querySelector('.descuento-input').value));
                        const bruto = Math.round(galones * vrUnitario);
                        cargo = Math.max(0, bruto - descuento);

                        brutoInput.value = formatNumber(bruto);
                        vrNetoCargoInput.value = formatNumber(cargo);
                    }

                    const abonos = Math.round(parseNumber(row.querySelector('.abonos-input').value));
                    const saldo = saldoPrevio + cargo - abonos;
                    row.querySelector('.saldo-input').value = formatNumber(saldo);

                    return { saldo, cargo, abonos };
                }

                function updateTotals() {
                    let saldo = saldoAnterior;
                    let totalCargos = 0;
                    let totalAbonos = 0;
                    const rows = tbody.querySelectorAll('tr');

                    rows.forEach(row => {
                        const result = updateRow(row, saldo);
                        saldo = result.saldo;
                        totalCargos += result.cargo;
                        totalAbonos += result.abonos;
                    });

                    document.getElementById('total-cargos').textContent = formatNumber(totalCargos);
                    document.getElementById('total-abonos').textContent = formatNumber(totalAbonos);
                    document.getElementById('saldo-final').textContent = formatNumber(saldo);
                    sinMovimientos.classList.toggle('d-none', rows.length > 0);
                }

                function attachEvents(row) {
                    row.querySelectorAll('.galones-input, .money-input').forEach(input => {
                        input.addEventListener('input', updateTotals);
                        input.addEventListener('blur', function() {
                            if (input.value.trim() === '') {
                                return;
                            }

                            const decimals = input.classList.contains('galones-input') ? 3 : 0;
                            input.value = formatNumber(parseNumber(input.value), decimals);
                            updateTotals();
                        });
                    });
                }

                function createRow(index) {
                    const tr = document.createElement('tr');
                    tr.dataset.index = index;
                    tr.dataset.origen = 'manual';
                    tr.innerHTML = `
                        <td><input type="text" name="detalles[${index}][planillas]" class="form-control form-control-sm"></td>
                        <td><input type="date" name="detalles[${index}][fecha]" class="form-control form-control-sm" required></td>
                        <td><input type="text" name="detalles[${index}][factura]" class="form-control form-control-sm"></td>
                        <td><input type="text" name="detalles[${index}][placas]" class="form-control form-control-sm"></td>
                        <td><input type="text" name="detalles[${index}][producto]" class="form-control form-control-sm"></td>
                        <td><input type="text" name="detalles[${index}][galones]" class="form-control form-control-sm text-end galones-input" inputmode="decimal"></td>
                        <td><input type="text" name="detalles[${index}][vr_unitario]" class="form-control form-control-sm text-end vr-unitario-input money-input" inputmode="decimal"></td>
                        <td><input type="text" class="form-control form-control-sm text-end bruto-input" readonly></td>
                        <td><input type="text" name="detalles[${index}][descuento]" class="form-control form-control-sm text-end descuento-input money-input" inputmode="decimal"></td>
                        <td><input type="text" name="detalles[${index}][cuenta]" class="form-control form-control-sm"></td>
                        <td><input type="text" name="detalles[${index}][concepto]" class="form-control form-control-sm"></td>
                        <td><input type="text" name="detalles[${index}][tercero]" class="form-control form-control-sm"></td>
                        <td><input type="text" name="detalles[${index}][nit]" class="form-control form-control-sm"></td>
                        <td><input type="text" class="form-control form-control-sm text-end vr-neto-cargo-input" readonly></td>
                        <td><input type="text" name="detalles[${index}][abonos]" class="form-control form-control-sm text-end abonos-input money-input" inputmode="decimal"></td>
                        <td><input type="text" class="form-control form-control-sm text-end saldo-input" readonly></td>
                        <td class="text-center"><button type="button" class="btn btn-sm btn-danger remove-row">×</button></td>
                    `;

                    return tr;
                }

                tbody.querySelectorAll('tr').forEach(row => attachEvents(row));

                addBtn.addEventListener('click', function() {
                    const row = createRow(nextIndex);
                    tbody.appendChild(row);
                    attachEvents(row);
                    nextIndex += 1;
                    updateTotals();
                });

                tbody.addEventListener('click', function(e) {
                    const button = e.target.closest('.remove-row');
                    if (!button) {
                        return;
                    }

                    const row = button.closest('tr');

                    // Movimiento manual ya guardado: se marca para eliminarlo al guardar.
                    if (row.dataset.id) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'eliminar[]';
                        input.value = row.dataset.id;
                        eliminarContainer.appendChild(input);
                    }

                    row.remove();
                    updateTotals();
                });

                updateTotals();
            });
        </script>
    @endif
@endsection
