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

        /* En ventas según lectura electrónica los valores van dentro de un campo: se suma su margen interno para que coincidan. */
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

        /* Avisos de guardado grandes: que el usuario no se vaya sin saber si guardó. */
        .le-aviso {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.25rem;
            border-radius: .75rem;
            font-size: 1.15rem;
            margin-bottom: 1rem;
        }

        .le-aviso > .bi {
            font-size: 2.4rem;
            flex-shrink: 0;
        }

        .le-aviso strong {
            display: block;
            font-size: 1.35rem;
        }

        .le-aviso-ok {
            background: #d1e7dd;
            border: 2px solid #198754;
            color: #0f5132;
        }

        .le-aviso-error {
            background: #f8d7da;
            border: 2px solid #dc3545;
            color: #842029;
        }

        .le-sin-guardar {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1050;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: .75rem 1.25rem;
            padding: .9rem 1rem;
            background: #fff3cd;
            border-top: 4px solid #e0a800;
            box-shadow: 0 -.5rem 1.25rem rgba(0, 0, 0, .18);
            color: #664d03;
            font-size: 1.1rem;
            font-weight: 600;
            animation: le-parpadeo 1.6s ease-in-out infinite;
        }

        .le-sin-guardar[hidden] {
            display: none;
        }

        .le-sin-guardar .bi {
            font-size: 1.8rem;
        }

        .le-sin-guardar .btn {
            font-size: 1.1rem;
            font-weight: 800;
            padding: .55rem 1.6rem;
        }

        @keyframes le-parpadeo {
            50% { background: #ffe69c; }
        }

        body.con-lectura-sin-guardar main {
            padding-bottom: 6rem;
        }

        .le-guardar-grande {
            font-size: 1.15rem;
            font-weight: 800;
            padding: .7rem 1.5rem;
        }

        @media print {
            .le-sin-guardar { display: none !important; }
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
        <div class="le-aviso le-aviso-ok" id="lectura-guardada" role="status">
            <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
            <div>
                <strong>LECTURAS DEL {{ Carbon::parse($fecha)->format('d/m/Y') }} GUARDADAS</strong>
                {{ session('success') }} Ya quedaron en el sistema ({{ now()->format('h:i a') }}).
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="le-aviso le-aviso-error" role="alert">
            <i class="bi bi-x-octagon-fill" aria-hidden="true"></i>
            <div>
                <strong>LAS LECTURAS NO SE GUARDARON</strong>
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Alerta: diferencia mayor a $3.000 entre la lectura electrónica y los cierres de IAPROPIADA. --}}
    @if ($alertas !== [])
        @php($textoAlertas = collect($alertas)->map(fn ($a) => $a['nombre'].': '.($a['valor'] < 0 ? 'FALTANTE' : 'SOBRANTE').' de $'.$pesos(abs($a['valor'])))->implode(' · '))
        <div class="alert alert-danger d-flex align-items-start gap-2" id="alerta-lectura">
            <i class="bi bi-exclamation-triangle-fill fs-5" aria-hidden="true"></i>
            <div>
                <strong>Diferencia mayor a ${{ $pesos(\App\Services\LecturaElectronicaService::UMBRAL_ALERTA) }}</strong>
                entre la lectura electrónica y los cierres de IAPROPIADA del {{ Carbon::parse($fecha)->format('d/m/Y') }}.
                <div>{{ $textoAlertas }}</div>
                <small>Revise las lecturas finales y las planillas de turno del día.</small>
            </div>
        </div>

        @if (session('success'))
            {{-- Al guardar, además, en una ventana para que no pase desapercibida. --}}
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: 'Lecturas guardadas, pero con diferencia mayor a ${{ $pesos(\App\Services\LecturaElectronicaService::UMBRAL_ALERTA) }}',
                        text: @json($textoAlertas),
                        icon: 'warning',
                        confirmButtonText: 'Entendido',
                    });
                });
            </script>
        @endif
    @elseif (session('success'))
        {{-- Confirmación grande del guardado. --}}
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Lecturas guardadas',
                    html: '<p style="font-size:1.15rem">Las lecturas del {{ Carbon::parse($fecha)->format('d/m/Y') }} quedaron guardadas en el sistema.</p>',
                    icon: 'success',
                    confirmButtonText: 'Entendido',
                    timer: 4000,
                    timerProgressBar: true,
                });
            });
        </script>
    @endif

    <div class="row g-3">
        <div class="col-lg-6">
            <form method="POST" action="{{ route('lectura-electronica.guardar') }}" id="lectura-electronica-form"
                @if (session('success')) data-guardado-ok="1" @endif>
                @csrf
                <input type="hidden" name="fecha" value="{{ $fecha }}">

                <div class="card">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <strong>LECTURA ELECTRÓNICA — {{ Carbon::parse($fecha)->format('d/m/Y') }}</strong>
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
                    <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <span class="small text-muted">Lo digitado se guarda en el sistema solo al pulsar GUARDAR.</span>
                        <button type="submit" class="btn btn-success le-guardar-grande">
                            <i class="bi bi-save" aria-hidden="true"></i> GUARDAR LECTURAS
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-lg-6">
            <div class="card mb-3">
                <div class="card-header"><strong>VENTAS SEGÚN LECTURA ELECTRÓNICA</strong></div>
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
                <div class="card-header"><strong>VENTAS SEGÚN CIERRES DE IAPROPIADA DEL DÍA</strong></div>
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
                <div class="card-header"><strong>SOBRANTE O FALTANTE x LECTURA SURTIDORES</strong></div>
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
                    Ventas según lectura electrónica − ventas según cierres de IAPROPIADA del día. Positivo: sobrante.
                    Negativo (en rojo): faltante. Se actualiza al GUARDAR.
                </div>
            </div>
        </div>
    </div>

    {{-- Barra fija mientras haya lecturas digitadas sin guardar (resources/js/lectura-autosave.js). --}}
    <div class="le-sin-guardar d-print-none" id="lectura-sin-guardar" role="alert" hidden>
        <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
        <span id="lectura-sin-guardar-texto">Hay lecturas SIN GUARDAR: pulse GUARDAR antes de salir o apagar el equipo.</span>
        <button type="submit" form="lectura-electronica-form" class="btn btn-success">
            <i class="bi bi-save" aria-hidden="true"></i> GUARDAR AHORA
        </button>
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
