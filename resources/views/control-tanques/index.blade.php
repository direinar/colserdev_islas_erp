@extends('layouts.app')

@section('title', 'Control de Tanques')

@php
    use App\Models\TanqueSaldoInicial;
    use Illuminate\Support\Carbon;

    $combustibles = [TanqueSaldoInicial::CORRIENTE => 'CORRIENTE', TanqueSaldoInicial::ACPM => 'A C P M'];
    $galones = fn (?float $valor): string => $valor === null ? '' : number_format($valor, 2, ',', '.');
    $galonesOVacio = fn (?float $valor): string => $valor ? number_format($valor, 2, ',', '.') : '';
    // FALTANTE (SOBRANTE): el sobrante (negativo) va entre paréntesis, como en la plantilla.
    $faltante = fn (?float $valor): string => $valor === null
        ? ''
        : ($valor < 0 ? '('.number_format(abs($valor), 2, ',', '.').')' : number_format($valor, 2, ',', '.'));
    // Campos editables sin separador de miles: NumberParser::quantity lee un punto solo como
    // decimal, así que "3.350" volvería como 3,35 al guardar. Coma = decimales.
    $aforoInput = fn (?float $valor, int $decimales): string => $valor === null
        ? ''
        : rtrim(rtrim(number_format($valor, $decimales, ',', ''), '0'), ',');
    $esAdministrador = auth()->user()->isAdministrador();
    $sinSaldoInicial = collect(array_keys($combustibles))->reject(fn ($c) => $saldosIniciales->has($c));
@endphp

@section('content')
    <style>
        .control-tanques-table th,
        .control-tanques-table td {
            white-space: nowrap;
            vertical-align: middle;
        }

        .control-tanques-table input.form-control-sm {
            min-width: 5.5rem;
            text-align: right;
        }

        .control-tanques-table .bloque-corriente {
            background-color: #fff8ec;
        }

        .control-tanques-table .bloque-acpm {
            background-color: #eef6ff;
        }

        @page {
            size: landscape;
            margin: 7mm 6mm 8mm;
        }

        @media print {
            nav,
            .navbar {
                display: none !important;
            }

            body > .container-fluid {
                padding: 0 !important;
            }

            .card,
            .card-body {
                border: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
            }

            .control-tanques-table input.form-control-sm {
                border: 0 !important;
                padding: 0 !important;
                background: transparent !important;
            }

            .control-tanques-table {
                font-size: 9px;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>

    <div class="pastel-section mb-3 d-print-none">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-end gap-2">
            <div>
                <h4 class="mb-0 text-warning-emphasis fw-bold">INFORME DE CONTROL DE TANQUES</h4>
                <small class="text-muted">Solo galones. Ingresos desde compras y ventas según cortes de IAPROPIADA de la
                    planilla de turnos (suma de los turnos de cada fecha); el aforo lo digita el jefe de patios cada noche.</small>
            </div>

            <form method="GET" action="{{ route('control-tanques.index') }}" class="d-flex flex-wrap gap-2 align-items-end">
                <div>
                    <label class="form-label small mb-1" for="desde">Fecha desde</label>
                    <input type="date" name="desde" id="desde" class="form-control form-control-sm" value="{{ $desde }}"
                        onchange="this.form.submit()">
                </div>
                <div>
                    <label class="form-label small mb-1" for="hasta">Fecha hasta</label>
                    <input type="date" name="hasta" id="hasta" class="form-control form-control-sm" value="{{ $hasta }}"
                        onchange="this.form.submit()">
                </div>
                <button type="submit" form="aforos-form" class="btn btn-primary btn-sm">
                    <i class="bi bi-save"></i> Guardar aforos
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                    <i class="bi bi-printer"></i> Imprimir
                </button>
                <a href="{{ route('control-tanques.exportar', ['desde' => $desde, 'hasta' => $hasta]) }}"
                    class="btn btn-outline-success btn-sm">
                    <i class="bi bi-file-earmark-excel"></i> Excel
                </a>
            </form>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success d-print-none">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger d-print-none">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @if ($sinSaldoInicial->isNotEmpty())
        <div class="alert alert-warning d-print-none">
            Falta el saldo inicial de {{ $sinSaldoInicial->map(fn ($c) => $combustibles[$c])->implode(' y ') }}:
            mientras no exista, el saldo se calcula desde cero con toda la historia de compras y ventas.
            @unless ($esAdministrador)
                Solicítelo al administrador.
            @endunless
        </div>
    @endif

    @if ($esAdministrador)
        <div class="card mb-3 d-print-none">
            <div class="card-body py-2">
                <div class="fw-bold small mb-2">Saldo inicial de los tanques (galones al iniciar el día de corte)</div>
                <div class="row g-2">
                    @foreach ($combustibles as $combustible => $nombre)
                        @php($saldoInicial = $saldosIniciales->get($combustible))
                        <form method="POST" action="{{ route('control-tanques.saldo-inicial') }}"
                            class="col-12 col-lg-6 d-flex flex-wrap gap-2 align-items-end">
                            @csrf
                            <input type="hidden" name="combustible" value="{{ $combustible }}">
                            <input type="hidden" name="desde" value="{{ $desde }}">
                            <input type="hidden" name="hasta" value="{{ $hasta }}">
                            <div class="fw-semibold small pb-1" style="min-width:5.5rem;">{{ $nombre }}</div>
                            <div>
                                <label class="form-label small mb-1">Fecha de corte</label>
                                <input type="date" name="fecha_corte" class="form-control form-control-sm" required
                                    value="{{ $saldoInicial?->fecha_corte->format('Y-m-d') }}">
                            </div>
                            <div>
                                <label class="form-label small mb-1">Galones</label>
                                <input type="text" name="galones" class="form-control form-control-sm text-end" required
                                    inputmode="decimal"
                                    value="{{ $saldoInicial ? $aforoInput((float) $saldoInicial->galones, 3) : '' }}">
                            </div>
                            <button type="submit" class="btn btn-outline-primary btn-sm">Guardar</button>
                        </form>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="d-none d-print-block mb-2">
        <div class="fw-bold">INFORME DE CONTROL DE TANQUES</div>
        <div class="small">Periodo: {{ Carbon::parse($desde)->format('d/m/Y') }} al {{ Carbon::parse($hasta)->format('d/m/Y') }}</div>
    </div>

    <form method="POST" action="{{ route('control-tanques.aforos') }}" id="aforos-form">
        @csrf
        <input type="hidden" name="desde" value="{{ $desde }}">
        <input type="hidden" name="hasta" value="{{ $hasta }}">

        <div class="card">
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0 control-tanques-table">
                    <thead>
                        <tr style="background-color:#f6e7d4;">
                            <th rowspan="3" class="text-center">FECHA DE<br>VENTA</th>
                            @foreach ($combustibles as $combustible => $nombre)
                                <th colspan="6" class="text-center bloque-{{ $combustible }}">{{ $nombre }}</th>
                            @endforeach
                            <th colspan="2" class="text-center">SALDOS</th>
                        </tr>
                        <tr style="background-color:#f6e7d4;">
                            @foreach ($combustibles as $combustible => $nombre)
                                <th colspan="3" class="text-center bloque-{{ $combustible }}">MOVIMIENTO DE GALONES</th>
                                <th class="text-center bloque-{{ $combustible }}">AFORO</th>
                                <th class="text-center bloque-{{ $combustible }}">SALDO FINAL</th>
                                <th class="text-center bloque-{{ $combustible }}">FALTANTE</th>
                            @endforeach
                            <th colspan="2" class="text-center">VENTA (GALONES)</th>
                        </tr>
                        <tr style="background-color:#f6e7d4;">
                            @foreach ($combustibles as $combustible => $nombre)
                                <th class="text-center bloque-{{ $combustible }}">INGRESO</th>
                                <th class="text-center bloque-{{ $combustible }}">VENTA</th>
                                <th class="text-center bloque-{{ $combustible }}">SALDO</th>
                                <th class="text-center bloque-{{ $combustible }}">S/VARA</th>
                                <th class="text-center bloque-{{ $combustible }}">S/TABLA AFORO</th>
                                <th class="text-center bloque-{{ $combustible }}">(SOBRANTE)</th>
                            @endforeach
                            <th class="text-center">DIARIA</th>
                            <th class="text-center">ACUMULADA</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr class="fw-bold">
                            <td></td>
                            @foreach ($combustibles as $combustible => $nombre)
                                <td class="bloque-{{ $combustible }}"></td>
                                <td class="text-end bloque-{{ $combustible }}">VIENEN</td>
                                <td class="text-end bloque-{{ $combustible }}">{{ $galones($vienen[$combustible]) }}</td>
                                <td class="bloque-{{ $combustible }}" colspan="3"></td>
                            @endforeach
                            <td></td>
                            <td class="text-end">{{ $galones(0) }}</td>
                        </tr>

                        @foreach ($filas as $fila)
                            <tr>
                                <td class="text-center">{{ Carbon::parse($fila['fecha'])->format('d/m/Y') }}</td>
                                @foreach ($combustibles as $combustible => $nombre)
                                    @php($dato = $fila[$combustible])
                                    <td class="text-end bloque-{{ $combustible }}">{{ $galonesOVacio($dato['ingreso']) }}</td>
                                    <td class="text-end bloque-{{ $combustible }}">{{ $galonesOVacio($dato['venta']) }}</td>
                                    <td class="text-end fw-semibold bloque-{{ $combustible }}">{{ $galones($dato['saldo']) }}</td>
                                    <td class="bloque-{{ $combustible }}">
                                        <input type="text" class="form-control form-control-sm" inputmode="decimal"
                                            name="aforos[{{ $fila['fecha'] }}][vara_{{ $combustible }}]"
                                            value="{{ $aforoInput($dato['vara'], 2) }}" aria-label="Aforo vara {{ $nombre }} {{ $fila['fecha'] }}">
                                    </td>
                                    <td class="bloque-{{ $combustible }}">
                                        <input type="text" class="form-control form-control-sm" inputmode="decimal"
                                            name="aforos[{{ $fila['fecha'] }}][tabla_{{ $combustible }}]"
                                            value="{{ $aforoInput($dato['tabla'], 3) }}" aria-label="Galones tabla de aforo {{ $nombre }} {{ $fila['fecha'] }}">
                                    </td>
                                    <td class="text-end bloque-{{ $combustible }} {{ ($dato['faltante'] ?? 0) > 0 ? 'text-danger fw-bold' : '' }}">
                                        {{ $faltante($dato['faltante']) }}
                                    </td>
                                @endforeach
                                <td class="text-end">{{ $galonesOVacio($fila['venta_diaria']) }}</td>
                                <td class="text-end">{{ $galones($fila['venta_acumulada']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>

                    <tfoot>
                        <tr class="table-secondary fw-bold">
                            <td class="text-end">TOTALES</td>
                            @foreach ($combustibles as $combustible => $nombre)
                                <td class="text-end">{{ $galones($totales['ingreso_'.$combustible]) }}</td>
                                <td class="text-end">{{ $galones($totales['venta_'.$combustible]) }}</td>
                                <td colspan="4"></td>
                            @endforeach
                            <td class="text-end">{{ $galones($totales['venta_diaria']) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </form>

    <div class="small text-muted mt-2">ESTE INFORME SOLO CONTIENE GALONES. Faltante = saldo en libros − saldo final
        según tabla de aforo; entre paréntesis es sobrante.</div>
@endsection
