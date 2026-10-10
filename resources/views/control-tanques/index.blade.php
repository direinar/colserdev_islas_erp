@extends('layouts.app')

@section('title', 'Control de Tanques')

@php
    use App\Models\TanqueSaldoInicial;
    use Illuminate\Support\Carbon;

    $combustibles = [TanqueSaldoInicial::CORRIENTE => 'CORRIENTE', TanqueSaldoInicial::ACPM => 'A C P M'];
    // Como en la plantilla: galones enteros sin decimales (3.350) y con dos decimales solo si los tienen.
    $galones = fn (?float $valor): string => $valor === null
        ? ''
        : number_format($valor, fmod(round($valor, 2), 1.0) == 0.0 ? 0 : 2, ',', '.');
    $galonesOVacio = fn (?float $valor): string => $valor ? $galones($valor) : '';
    // Campos editables sin separador de miles: NumberParser::quantity lee un punto solo como
    // decimal, así que "3.350" volvería como 3,35 al guardar. Coma = decimales.
    $aforoInput = fn (?float $valor, int $decimales): string => $valor === null
        ? ''
        : rtrim(rtrim(number_format($valor, $decimales, ',', ''), '0'), ',');
    $varaInput = fn (?float $valor): string => $valor === null ? '' : number_format($valor, 2, ',', '');
    $esAdministrador = auth()->user()->isAdministrador();
    $sinSaldoInicial = collect(array_keys($combustibles))->reject(fn ($c) => $saldosIniciales->has($c));
@endphp

@section('content')
    <style>
        /* Colores y separadores de la plantilla "INFORME DE CONTROL TANQUES". */
        .ct-tabla {
            --ct-corriente: #e2efda;
            --ct-corriente-aforo: #bfbfbf;
            --ct-acpm: #c6e0b4;
            --ct-acpm-aforo: #92d050;
            --ct-saldos: #d6dce4;
            --ct-separador: #262626;
            border-collapse: collapse;
            width: 100%;
            font-size: .85rem;
            color: #000;
        }

        .ct-tabla th,
        .ct-tabla td {
            border: 1px solid #8c8c8c;
            padding: .2rem .4rem;
            white-space: nowrap;
            vertical-align: middle;
        }

        .ct-tabla thead th {
            text-align: center;
            font-weight: 700;
            font-size: .72rem;
            line-height: 1.15;
        }

        .ct-tabla thead .ct-bloque {
            font-size: 1.35rem;
            letter-spacing: .05rem;
            padding: .3rem;
        }

        .ct-tabla td {
            text-align: right;
            height: 1.9rem;
        }

        .ct-tabla .ct-fecha {
            text-align: center;
            background: #fff;
            min-width: 6rem;
        }

        .ct-tabla .ct-vienen {
            font-weight: 700;
        }

        .ct-tabla .ct-corriente { background: var(--ct-corriente); }
        .ct-tabla .ct-corriente-aforo { background: var(--ct-corriente-aforo); }
        .ct-tabla .ct-acpm { background: var(--ct-acpm); }
        .ct-tabla .ct-acpm-aforo { background: var(--ct-acpm-aforo); }
        .ct-tabla .ct-saldos { background: var(--ct-saldos); }

        /* Separador grueso al inicio de cada bloque y antes del aforo, como en el Excel. */
        .ct-tabla .ct-inicio-bloque { border-left: 5px solid var(--ct-separador); }
        .ct-tabla .ct-inicio-aforo { border-left: 3px solid var(--ct-separador); }

        .ct-tabla .ct-saldo { font-weight: 700; }
        .ct-tabla .ct-faltante { font-weight: 700; font-size: 1rem; }
        .ct-tabla .ct-negativo { color: #ff0000; }
        .ct-tabla .ct-sobrante-titulo { color: #ff0000; }

        /* El aforo se digita en la celda, como en una hoja de cálculo. */
        .ct-tabla .ct-celda-input { padding: 0; }

        .ct-tabla .ct-input {
            width: 100%;
            min-width: 5.5rem;
            border: 0;
            background: transparent;
            text-align: right;
            padding: .2rem .4rem;
            font-weight: 600;
        }

        .ct-tabla .ct-input:focus {
            outline: 2px solid #1f6feb;
            outline-offset: -2px;
            background: #fff;
        }

        .ct-tabla tfoot td {
            font-weight: 700;
            background: #f2f2f2;
        }

        .ct-titulo {
            color: #ff0000;
            font-weight: 800;
            font-size: 1.6rem;
            letter-spacing: .03rem;
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

            .ct-tabla {
                font-size: 9px;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .ct-tabla .ct-input {
                min-width: 0;
                padding: 0 .2rem;
            }
        }
    </style>

    <div class="card mb-3">
        <div class="card-body py-2 d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-2">
            <div>
                <div class="ct-titulo">INFORME DE CONTROL DE TANQUES</div>
                <div class="small d-none d-print-block">
                    FECHA DESDE {{ Carbon::parse($desde)->format('d/m/Y') }} — FECHA HASTA {{ Carbon::parse($hasta)->format('d/m/Y') }}
                </div>
            </div>

            <form method="GET" action="{{ route('control-tanques.index') }}" class="d-flex flex-wrap gap-2 align-items-end d-print-none">
                <div>
                    <label class="form-label small fw-semibold mb-1" for="desde">FECHA DESDE</label>
                    <input type="date" name="desde" id="desde" class="form-control form-control-sm" value="{{ $desde }}"
                        onchange="this.form.submit()">
                </div>
                <div>
                    <label class="form-label small fw-semibold mb-1" for="hasta">FECHA HASTA</label>
                    <input type="date" name="hasta" id="hasta" class="form-control form-control-sm" value="{{ $hasta }}"
                        onchange="this.form.submit()">
                </div>
                <button type="submit" form="aforos-form" class="btn btn-primary btn-sm">
                    <i class="bi bi-save"></i> GUARDAR AFOROS
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                    <i class="bi bi-printer"></i> IMPRIMIR
                </button>
                <a href="{{ route('control-tanques.exportar', ['desde' => $desde, 'hasta' => $hasta]) }}"
                    class="btn btn-outline-success btn-sm">
                    <i class="bi bi-file-earmark-excel"></i> EXCEL
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

    <form method="POST" action="{{ route('control-tanques.aforos') }}" id="aforos-form">
        @csrf
        <input type="hidden" name="desde" value="{{ $desde }}">
        <input type="hidden" name="hasta" value="{{ $hasta }}">

        <div class="card">
            <div class="table-responsive">
                <table class="ct-tabla">
                    <thead>
                        <tr>
                            <th class="ct-fecha"></th>
                            @foreach ($combustibles as $combustible => $nombre)
                                <th colspan="6" class="ct-bloque ct-{{ $combustible }} ct-inicio-bloque">{{ $nombre }}</th>
                            @endforeach
                            <th colspan="2" class="ct-bloque ct-saldos ct-inicio-bloque">SALDOS</th>
                        </tr>
                        <tr>
                            <th rowspan="2" class="ct-fecha">FECHA DE<br>VENTA</th>
                            @foreach ($combustibles as $combustible => $nombre)
                                <th colspan="3" class="ct-{{ $combustible }} ct-inicio-bloque">MOVIMIENTO DE GALONES</th>
                                <th class="ct-{{ $combustible }}-aforo ct-inicio-aforo">AFORO</th>
                                <th class="ct-{{ $combustible }}-aforo">SALDO FINAL</th>
                                <th class="ct-{{ $combustible }}">FALTANTE</th>
                            @endforeach
                            <th class="ct-saldos ct-inicio-bloque">VR. VENTA</th>
                            <th class="ct-saldos">VR. VENTA</th>
                        </tr>
                        <tr>
                            @foreach ($combustibles as $combustible => $nombre)
                                <th class="ct-{{ $combustible }} ct-inicio-bloque">INGRESO</th>
                                <th class="ct-{{ $combustible }}">VENTA</th>
                                <th class="ct-{{ $combustible }}">SALDO</th>
                                <th class="ct-{{ $combustible }}-aforo ct-inicio-aforo">S/VARA</th>
                                <th class="ct-{{ $combustible }}-aforo">S/TABLA AFORO</th>
                                <th class="ct-{{ $combustible }} ct-sobrante-titulo">(SOBRANTE)</th>
                            @endforeach
                            <th class="ct-saldos ct-inicio-bloque">DIARIA</th>
                            <th class="ct-saldos">ACUMULADA</th>
                        </tr>
                    </thead>

                    <tbody>
                        {{-- Saldo con el que arranca el periodo: va en la columna de fecha, no en VENTA. --}}
                        <tr>
                            <td class="ct-fecha ct-vienen">VIENEN</td>
                            @foreach ($combustibles as $combustible => $nombre)
                                <td class="ct-{{ $combustible }} ct-inicio-bloque"></td>
                                <td class="ct-{{ $combustible }}"></td>
                                <td class="ct-{{ $combustible }} ct-saldo" data-vienen="{{ $combustible }}">{{ $galones($vienen[$combustible]) }}</td>
                                <td class="ct-{{ $combustible }}-aforo ct-inicio-aforo"></td>
                                <td class="ct-{{ $combustible }}-aforo"></td>
                                <td class="ct-{{ $combustible }}"></td>
                            @endforeach
                            <td class="ct-saldos ct-inicio-bloque"></td>
                            <td class="ct-saldos">0</td>
                        </tr>

                        @foreach ($filas as $fila)
                            <tr>
                                <td class="ct-fecha">{{ Carbon::parse($fila['fecha'])->format('d/m/Y') }}</td>
                                @foreach ($combustibles as $combustible => $nombre)
                                    @php($dato = $fila[$combustible])
                                    <td class="ct-{{ $combustible }} ct-inicio-bloque">{{ $galonesOVacio($dato['ingreso']) }}</td>
                                    <td class="ct-{{ $combustible }}">{{ $galonesOVacio($dato['venta']) }}</td>
                                    <td class="ct-{{ $combustible }} ct-saldo">{{ $galones($dato['saldo']) }}</td>
                                    <td class="ct-{{ $combustible }}-aforo ct-inicio-aforo ct-celda-input">
                                        <input type="text" class="ct-input" inputmode="decimal"
                                            name="aforos[{{ $fila['fecha'] }}][vara_{{ $combustible }}]"
                                            value="{{ $varaInput($dato['vara']) }}" aria-label="Aforo vara {{ $nombre }} {{ $fila['fecha'] }}">
                                    </td>
                                    <td class="ct-{{ $combustible }}-aforo ct-celda-input">
                                        <input type="text" class="ct-input" inputmode="decimal"
                                            name="aforos[{{ $fila['fecha'] }}][tabla_{{ $combustible }}]"
                                            value="{{ $aforoInput($dato['tabla'], 3) }}" aria-label="Galones tabla de aforo {{ $nombre }} {{ $fila['fecha'] }}">
                                    </td>
                                    <td class="ct-{{ $combustible }} ct-faltante {{ ($dato['faltante'] ?? 0) < 0 ? 'ct-negativo' : '' }}">{{ $galones($dato['faltante']) }}</td>
                                @endforeach
                                <td class="ct-saldos ct-inicio-bloque">{{ $galones($fila['venta_diaria']) }}</td>
                                <td class="ct-saldos">{{ $galones($fila['venta_acumulada']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>

                    <tfoot>
                        <tr>
                            <td class="ct-fecha">TOTALES</td>
                            @foreach ($combustibles as $combustible => $nombre)
                                <td class="ct-inicio-bloque">{{ $galones($totales['ingreso_'.$combustible]) }}</td>
                                <td>{{ $galones($totales['venta_'.$combustible]) }}</td>
                                <td></td>
                                <td class="ct-inicio-aforo"></td>
                                <td></td>
                                <td></td>
                            @endforeach
                            <td class="ct-inicio-bloque">{{ $galones($totales['venta_diaria']) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </form>

    <div class="small text-muted mt-2">ESTE INFORME SOLO CONTIENE GALONES. Ingresos desde compras y ventas según cortes de
        IAPROPIADA de la planilla de turnos (suma de los turnos de cada fecha). FALTANTE = saldo en libros − saldo final
        según tabla de aforo; en rojo (negativo) es sobrante.</div>
@endsection
