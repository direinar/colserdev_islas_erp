@extends('layouts.app')

@section('title', 'Lectura Electrónica')

@php
    use Illuminate\Support\Carbon;

    // Lecturas y galones con punto decimal y coma de miles, igual que en la planilla (galones.js).
    $lectura = fn (?float $valor): string => $valor === null ? '' : number_format($valor, 3, '.', ',');
    $pesos = fn (float $valor): string => number_format($valor, 0, ',', '.');
    $filasResumen = ['corriente' => 'CORRIENTE', 'acpm' => 'ACPM', 'total' => 'TOTAL'];
@endphp

@section('content')
    <style>
        .le-tabla th,
        .le-tabla td {
            vertical-align: middle;
        }

        .le-tabla thead th,
        .le-resumen thead th {
            background-color: #b5fdd2;
            text-align: center;
        }

        .le-resumen td {
            text-align: right;
        }

        /* Tabla A: MANGUERA a la izquierda como sus nombres; INICIAL, FINAL y GLS a la derecha
           como las lecturas, sumando el margen interno del campo. */
        .le-tabla thead th:first-child {
            text-align: left;
        }

        .le-tabla thead th:not(:first-child) {
            text-align: right;
            padding-right: calc(.75rem + 1px);
        }

        /* GALONES y VALOR alineados con sus valores (a la derecha). */
        .le-resumen thead th:not(:first-child) {
            text-align: right;
        }

        /* En (A) los valores van dentro de un campo: se suma su margen interno para que coincidan. */
        .le-resumen-campos thead th:not(:first-child),
        .le-resumen-campos .le-total td {
            padding-right: calc(.75rem + 1px);
        }

        .le-resumen .le-total {
            font-weight: 700;
            background: #f2f2f2;
        }

        .le-negativo {
            color: #ff0000;
        }
    </style>

    <x-page-header title="Lectura electrónica" icon="speedometer2"
        :subtitle="'Lecturas de los surtidores del '.Carbon::parse($fecha)->format('d/m/Y').' y sobrante o faltante frente a los cierres de IAPROPIADA.'">
        <form method="GET" action="{{ route('lectura-electronica.index') }}" class="d-flex align-items-end gap-2">
            <div>
                <label class="form-label" for="fecha">FECHA</label>
                <input type="date" name="fecha" id="fecha" class="form-control form-control-sm" value="{{ $fecha }}"
                    onchange="this.form.submit()">
            </div>
        </form>
        <button type="submit" form="lectura-electronica-form" class="btn btn-light btn-sm">
            <i class="bi bi-save" aria-hidden="true"></i> GUARDAR
        </button>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-6">
            <form method="POST" action="{{ route('lectura-electronica.guardar') }}" id="lectura-electronica-form">
                @csrf
                <input type="hidden" name="fecha" value="{{ $fecha }}">

                <div class="card">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <strong>A — LECTURA ELECTRÓNICA — {{ Carbon::parse($fecha)->format('d/m/Y') }}</strong>
                        {{-- Casi siempre solo algunas mangueras venden: se copian todas y se corrigen las que cambiaron. --}}
                        <button type="button" id="copiar-lecturas-btn" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-files"></i> COPIAR SALDOS INICIALES A FINALES
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0 le-tabla">
                            <thead>
                                <tr>
                                    <th>MANGUERA</th>
                                    <th>INICIAL</th>
                                    <th>FINAL</th>
                                    <th>GLS</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($filas as $fila)
                                    <tr data-combustible="{{ $fila['combustible'] }}">
                                        <td class="fw-semibold">{{ $fila['manguera'] }}</td>
                                        <td>
                                            {{-- La inicial es la final del día anterior: solo se digita si no hay lectura anterior. --}}
                                            <input type="text" name="iniciales[{{ $fila['manguera'] }}]" inputmode="decimal"
                                                class="form-control form-control-sm text-end lectura-inicial"
                                                value="{{ $lectura($fila['inicial']) }}" @readonly($fila['inicial_bloqueada'])>
                                        </td>
                                        <td>
                                            <input type="text" name="finales[{{ $fila['manguera'] }}]" inputmode="decimal"
                                                class="form-control form-control-sm text-end lectura-final" placeholder="0.000"
                                                value="{{ $lectura($fila['final']) }}">
                                        </td>
                                        <td>
                                            <input type="text" readonly class="form-control form-control-sm text-end lectura-gls"
                                                value="{{ $fila['final'] === null ? '' : $lectura($fila['galones']) }}">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-lg-6">
            <div class="card mb-3">
                <div class="card-header"><strong>(A) VENTAS SEGÚN LECTURA ELECTRÓNICA</strong></div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0 le-resumen le-resumen-campos">
                        <thead>
                            <tr>
                                <th></th>
                                <th>GALONES</th>
                                <th>VALOR</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th>CORRIENTE</th>
                                <td><input type="text" readonly class="form-control form-control-sm text-end ventas-lectura-galones-corriente"
                                        value="{{ $lectura($lecturas['corriente']['galones']) }}"></td>
                                <td><input type="text" readonly class="form-control form-control-sm text-end ventas-lectura-valor-corriente"
                                        data-precio="{{ $precios['corriente'] }}" value="{{ $pesos($lecturas['corriente']['valor']) }}"></td>
                            </tr>
                            <tr>
                                <th>ACPM</th>
                                <td><input type="text" readonly class="form-control form-control-sm text-end ventas-lectura-galones-acpm"
                                        value="{{ $lectura($lecturas['acpm']['galones']) }}"></td>
                                <td><input type="text" readonly class="form-control form-control-sm text-end ventas-lectura-valor-acpm"
                                        data-precio="{{ $precios['acpm'] }}" value="{{ $pesos($lecturas['acpm']['valor']) }}"></td>
                            </tr>
                            <tr class="le-total">
                                <th>TOTAL</th>
                                <td class="ventas-total-lectura-galones">{{ $lectura($lecturas['total']['galones']) }}</td>
                                <td class="ventas-total-lectura">{{ $pesos($lecturas['total']['valor']) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer small text-muted">
                    Precios del día: corriente {{ $pesos($precios['corriente']) }} — ACPM {{ $pesos($precios['acpm']) }}.
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><strong>(B) VENTAS SEGÚN CIERRES DE IAPROPIADA DEL DÍA</strong></div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0 le-resumen">
                        <thead>
                            <tr>
                                <th></th>
                                <th>GALONES</th>
                                <th>VALOR</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($filasResumen as $clave => $nombre)
                                <tr @class(['le-total' => $clave === 'total'])>
                                    <th>{{ $nombre }}</th>
                                    <td>{{ $lectura($iapropiada[$clave]['galones']) }}</td>
                                    <td>{{ $pesos($iapropiada[$clave]['valor']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer small text-muted">
                    Suma de las planillas de turno de la fecha (INFORMACIÓN DE VENTAS DEL TURNO), igual que el control de tanques.
                </div>
            </div>

            <div class="card">
                <div class="card-header"><strong>(C) SOBRANTE O FALTANTE x LECTURA SURTIDORES (A − B)</strong></div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0 le-resumen" id="le-diferencia">
                        <thead>
                            <tr>
                                <th></th>
                                <th>GALONES</th>
                                <th>VALOR</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($filasResumen as $clave => $nombre)
                                <tr @class(['le-total' => $clave === 'total'])>
                                    <th>{{ $nombre }}</th>
                                    <td @class(['le-negativo' => round($diferencia[$clave]['galones'], 3) < 0])>{{ $lectura($diferencia[$clave]['galones']) }}</td>
                                    <td @class(['le-negativo' => round($diferencia[$clave]['valor']) < 0])>{{ $pesos($diferencia[$clave]['valor']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer small text-muted">
                    Positivo: sobrante. Negativo (en rojo): faltante. Se actualiza al GUARDAR.
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const boton = document.getElementById('copiar-lecturas-btn');
            if (!boton) return;

            const filas = () => Array.from(document.querySelectorAll('#lectura-electronica-form tbody tr'));

            function copiar() {
                filas().forEach(row => {
                    const final = row.querySelector('.lectura-final');
                    final.value = row.querySelector('.lectura-inicial').value;
                    // galones.js recalcula GLS y la venta según lecturas con el evento input.
                    final.dispatchEvent(new Event('input', { bubbles: true }));
                });
            }

            boton.addEventListener('click', function() {
                const hayFinales = filas().some(row => row.querySelector('.lectura-final').value.trim() !== '');

                if (!hayFinales) {
                    copiar();
                    return;
                }

                Swal.fire({
                    title: '¿Reemplazar las lecturas finales?',
                    text: 'Ya hay lecturas finales digitadas. Se reemplazarán todas por las lecturas iniciales.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, copiar',
                    cancelButtonText: 'Cancelar',
                    reverseButtons: true,
                }).then(result => {
                    if (result.isConfirmed) copiar();
                });
            });
        });
    </script>
@endsection
