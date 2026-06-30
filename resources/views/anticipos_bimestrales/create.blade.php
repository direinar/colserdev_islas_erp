@extends('layouts.app')

@section('title', 'Anticipo Bimestral')

@section('content')
    <div class="pastel-section mb-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
            <div>
                <h4 class="mb-0 text-danger fw-bold">ANTICIPO BIMESTRAL</h4>
                <small class="text-muted">Informe bimestral con cálculo por fila y totales de galones y pesos.</small>
            </div>
            <button type="submit" form="anticipo-form" class="btn btn-primary btn-sm">Guardar informe</button>
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

    <form id="anticipo-form" method="POST" action="{{ route('anticipo-bimestral.store') }}">
        @csrf

        <x-erp-card title="INFORME ANTICIPO BIMESTRAL">
            <div class="p-3 pb-2">
                <div class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label small mb-1">Bimestre</label>
                        <input type="number" min="1" max="6" name="bimestre"
                            class="form-control form-control-sm" value="{{ old('bimestre', 2) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small mb-1">Periodo</label>
                        <input type="text" name="periodo" class="form-control form-control-sm"
                            value="{{ old('periodo', 'MARZO Y ABRIL') }}" required>
                    </div>
                    <div class="col-md-4 d-flex justify-content-md-end">
                        <button type="button" id="add-anticipo-row" class="btn btn-sm btn-outline-primary">+ Agregar
                            fila</button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0" id="anticipo-table">
                    <thead style="background-color:#fff000;">
                        <tr>
                            <th class="text-center align-middle">MES</th>
                            <th class="text-center align-middle">GALONES</th>
                            <th class="text-center align-middle">VALOR INTERMEDIACION</th>
                            <th class="text-center align-middle">PESOS</th>
                            <th class="text-center align-middle">ACCION</th>
                        </tr>
                    </thead>

                    <tbody id="anticipo-body">
                        @php
                            $rows = old('detalles', [
                                ['mes' => 'Mar-26', 'galones' => '', 'valor_intermediacion' => ''],
                            ]);
                        @endphp

                        @foreach ($rows as $index => $row)
                            <tr data-index="{{ $index }}">
                                <td>
                                    <input type="text" name="detalles[{{ $index }}][mes]"
                                        class="form-control form-control-sm" value="{{ $row['mes'] ?? '' }}"
                                        placeholder="Ej: Mar-26">
                                </td>
                                <td>
                                    <input type="text" name="detalles[{{ $index }}][galones]"
                                        class="form-control form-control-sm text-end galones-input" inputmode="decimal"
                                        value="{{ $row['galones'] ?? '' }}">
                                </td>
                                <td>
                                    <input type="text" name="detalles[{{ $index }}][valor_intermediacion]"
                                        class="form-control form-control-sm text-end valor-int-input" inputmode="decimal"
                                        value="{{ $row['valor_intermediacion'] ?? '' }}">
                                </td>
                                <td>
                                    <input type="text" name="detalles[{{ $index }}][pesos]"
                                        class="form-control form-control-sm text-end pesos-input" readonly>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-danger remove-row">×</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                    <tfoot>
                        <tr class="table-secondary fw-bold">
                            <td class="text-end">TOTAL</td>
                            <td id="total-galones" class="text-end">0</td>
                            <td></td>
                            <td id="total-pesos" class="text-end">0</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-erp-card>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tbody = document.getElementById('anticipo-body');
            const addBtn = document.getElementById('add-anticipo-row');
            let nextIndex = tbody.querySelectorAll('tr').length;

            function parseNumber(value) {
                if (!value) {
                    return 0;
                }

                const clean = value.toString()
                    .replace(/\./g, '')
                    .replace(/,/g, '.');

                return Number(clean) || 0;
            }

            function formatNumber(number, decimals = 0) {
                return Number(number).toLocaleString('es-CO', {
                    minimumFractionDigits: decimals,
                    maximumFractionDigits: decimals,
                });
            }

            function setFormattedValue(input, value, decimals = 0) {
                input.value = formatNumber(value, decimals);
            }

            function updateRow(row) {
                const galonesInput = row.querySelector('.galones-input');
                const valorIntInput = row.querySelector('.valor-int-input');
                const pesosInput = row.querySelector('.pesos-input');

                const galones = parseNumber(galonesInput.value);
                const valorIntermediacion = parseNumber(valorIntInput.value);
                const pesos = Math.round(galones * valorIntermediacion);

                setFormattedValue(pesosInput, pesos, 0);
            }

            function updateTotals() {
                let totalGalones = 0;
                let totalPesos = 0;

                tbody.querySelectorAll('tr').forEach(row => {
                    updateRow(row);

                    totalGalones += parseNumber(row.querySelector('.galones-input')?.value);
                    totalPesos += parseNumber(row.querySelector('.pesos-input')?.value);
                });

                document.getElementById('total-galones').textContent = formatNumber(totalGalones);
                document.getElementById('total-pesos').textContent = formatNumber(totalPesos);
            }

            function attachEvents(row) {
                row.querySelectorAll('.galones-input, .valor-int-input').forEach(input => {
                    input.addEventListener('input', updateTotals);
                    input.addEventListener('change', updateTotals);
                    input.addEventListener('blur', function() {
                        const value = parseNumber(input.value);
                        setFormattedValue(input, value, 2);
                    });
                });
            }

            function createRow(index) {
                const tr = document.createElement('tr');
                tr.dataset.index = index;
                tr.innerHTML = `
                    <td>
                        <input type="text" name="detalles[${index}][mes]" class="form-control form-control-sm" placeholder="Ej: Abr-26">
                    </td>
                    <td>
                        <input type="text" name="detalles[${index}][galones]" class="form-control form-control-sm text-end galones-input" inputmode="decimal">
                    </td>
                    <td>
                        <input type="text" name="detalles[${index}][valor_intermediacion]" class="form-control form-control-sm text-end valor-int-input" inputmode="decimal">
                    </td>
                    <td>
                        <input type="text" name="detalles[${index}][pesos]" class="form-control form-control-sm text-end pesos-input" readonly>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-danger remove-row">×</button>
                    </td>
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
                if (!e.target.classList.contains('remove-row')) {
                    return;
                }

                const rows = tbody.querySelectorAll('tr');
                if (rows.length === 1) {
                    rows[0].querySelectorAll('input').forEach(input => {
                        if (!input.classList.contains('pesos-input')) {
                            input.value = '';
                        }
                    });
                } else {
                    e.target.closest('tr').remove();
                }

                updateTotals();
            });

            updateTotals();
        });
    </script>
@endsection
