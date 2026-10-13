{{--
    Kardex de solo lectura. Todo se alimenta del saldo inicial, la planilla de turnos (ventas) y compras (entradas);
    las correcciones se hacen allá, no aquí.

    Parámetros: $kardex (KardexInventarioService), $titulo, $unidad ('GALONES' | 'UNIDADES'), $compraRoute,
    $mostrarReferencia (opcional: columna REFERENCIA para el consolidado de canastilla).
--}}
@php
    $mostrarReferencia = $mostrarReferencia ?? false;
    $columnas = $mostrarReferencia ? 13 : 12;
@endphp
<style>
    .kardex-table th,
    .kardex-table td {
        white-space: nowrap;
    }

    @page {
        size: landscape;
        margin: 8mm;
    }

    @media print {
        nav,
        .navbar {
            display: none !important;
        }

        body > .container-fluid {
            padding: 0 !important;
        }

        .kardex-table {
            font-size: 8px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .kardex-table a {
            color: inherit;
            text-decoration: none;
        }
    }
</style>

<x-erp-card :title="$titulo">
    <div class="table-responsive">
        <table class="table table-bordered table-sm mb-0 align-middle kardex-table">
            <thead>
                <tr style="background-color:#000; color:#ff1c1c; letter-spacing:.25rem; font-weight:800;">
                    <th colspan="{{ $columnas }}" class="text-center fs-5">{{ $titulo }}</th>
                </tr>
                <tr style="background-color:#f6e7d4;">
                    <th class="text-center align-middle">PLANILLA</th>
                    {{-- Una sola FECHA: la de la venta (planilla) o la de la compra (con enlace a la compra). --}}
                    <th rowspan="2" class="text-center align-middle">FECHA</th>
                    <th class="text-center align-middle">COMPRA</th>
                    @if ($mostrarReferencia)
                        <th rowspan="2" class="text-center align-middle">REFERENCIA</th>
                    @endif
                    <th colspan="3" class="text-center align-middle">{{ $unidad }}</th>
                    <th colspan="4" class="text-center align-middle">VALORES AL COSTO PROMEDIO</th>
                    <th colspan="2" class="text-center align-middle">VENTAS</th>
                </tr>
                <tr style="background-color:#f6e7d4;">
                    <th class="text-center">No.</th>
                    <th class="text-center">No. FC</th>
                    <th class="text-center">ENTRADAS</th>
                    <th class="text-center">SALIDAS</th>
                    <th class="text-center">SALDO</th>
                    <th class="text-center">ENTRADAS</th>
                    <th class="text-center">SALIDAS</th>
                    <th class="text-center">SALDO</th>
                    <th class="text-center">PROMEDIO</th>
                    <th class="text-center">VALOR VENTAS</th>
                    <th class="text-center">PRECIO ACTUAL</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($kardex['rows'] as $row)
                    <tr>
                        <td class="text-center">
                            @if ($row['numero_turno'])
                                <a href="{{ route('turnos.create', ['turno_busqueda' => $row['numero_turno']]) }}"
                                    class="text-decoration-none" title="Abrir planilla del turno">{{ $row['numero_turno'] }}</a>
                            @endif
                        </td>
                        <td class="text-center">
                            @if (in_array($row['tipo'], ['saldo_inicial', 'saldo_anterior'], true))
                                {{ \Illuminate\Support\Carbon::parse($row['fecha_compra'])->format('d/m/Y') }}
                            @elseif ($row['fecha_compra'])
                                <a href="{{ route($compraRoute, ['desde' => $row['fecha_compra'], 'hasta' => $row['fecha_compra']]) }}#compra-{{ $row['compra_id'] }}"
                                    class="text-decoration-none" title="Consultar la compra">{{ \Illuminate\Support\Carbon::parse($row['fecha_compra'])->format('d/m/Y') }}</a>
                            @elseif ($row['fecha_venta'])
                                {{ \Illuminate\Support\Carbon::parse($row['fecha_venta'])->format('d/m/Y') }}
                            @endif
                        </td>
                        <td>{{ $row['factura'] }}</td>
                        @if ($mostrarReferencia)
                            <td>{{ $row['referencia'] }}</td>
                        @endif
                        <td class="text-end">{{ $row['entradas'] > 0 ? number_format($row['entradas'], 0, ',', '.') : '' }}</td>
                        <td class="text-end">{{ $row['salidas'] > 0 ? number_format($row['salidas'], 2, ',', '.') : '' }}</td>
                        <td class="text-end">{{ number_format($row['saldo_unidades'], 2, ',', '.') }}</td>
                        <td class="text-end">{{ $row['valor_entradas'] > 0 ? number_format($row['valor_entradas'], 0, ',', '.') : '' }}</td>
                        <td class="text-end">{{ $row['salidas'] > 0 ? number_format($row['valor_salidas'], 0, ',', '.') : '' }}</td>
                        <td class="text-end">{{ number_format($row['valor_saldo'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($row['promedio'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ $row['tipo'] === 'venta' ? number_format($row['valor_venta'], 0, ',', '.') : '' }}</td>
                        <td class="text-end">{{ $row['precio'] !== null ? number_format($row['precio'], 0, ',', '.') : '' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $columnas }}" class="text-center text-muted py-3">No hay compras ni ventas registradas.</td>
                    </tr>
                @endforelse
            </tbody>

            <tfoot>
                <tr class="table-secondary fw-bold">
                    <td colspan="{{ $mostrarReferencia ? 4 : 3 }}" class="text-end">TOTALES</td>
                    <td class="text-end">{{ number_format($kardex['totales']['entradas'], 0, ',', '.') }}</td>
                    <td class="text-end">{{ number_format($kardex['totales']['salidas'], 2, ',', '.') }}</td>
                    <td class="text-end">{{ number_format($kardex['totales']['saldo_unidades'], 2, ',', '.') }}</td>
                    <td class="text-end">{{ number_format($kardex['totales']['valor_entradas'], 0, ',', '.') }}</td>
                    <td class="text-end">{{ number_format($kardex['totales']['valor_salidas'], 0, ',', '.') }}</td>
                    <td class="text-end">{{ number_format($kardex['totales']['valor_saldo'], 0, ',', '.') }}</td>
                    <td></td>
                    <td class="text-end">{{ number_format($kardex['totales']['valor_venta'], 0, ',', '.') }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</x-erp-card>
