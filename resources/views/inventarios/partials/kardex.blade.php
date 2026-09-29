{{--
    Kardex de solo lectura. Todo se alimenta de la planilla de turnos (ventas) y de compras (entradas);
    las correcciones se hacen allá, no aquí.

    Parámetros: $kardex (KardexInventarioService), $titulo, $unidad ('GALONES' | 'UNIDADES'), $compraRoute.
--}}
<style>
    .kardex-table th,
    .kardex-table td {
        white-space: nowrap;
    }
</style>

<x-erp-card :title="$titulo">
    <div class="table-responsive">
        <table class="table table-bordered table-sm mb-0 align-middle kardex-table">
            <thead>
                <tr style="background-color:#000; color:#ff1c1c; letter-spacing:.25rem; font-weight:800;">
                    <th colspan="13" class="text-center fs-5">{{ $titulo }}</th>
                </tr>
                <tr style="background-color:#f6e7d4;">
                    <th colspan="2" class="text-center align-middle">PLANILLA</th>
                    <th colspan="2" class="text-center align-middle">COMPRA</th>
                    <th colspan="3" class="text-center align-middle">{{ $unidad }}</th>
                    <th colspan="4" class="text-center align-middle">VALORES AL COSTO PROMEDIO</th>
                    <th colspan="2" class="text-center align-middle">VENTAS</th>
                </tr>
                <tr style="background-color:#f6e7d4;">
                    <th class="text-center">No.</th>
                    <th class="text-center">FECHA VENTA</th>
                    <th class="text-center">FECHA COMPRA</th>
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
                            {{ $row['fecha_venta'] ? \Illuminate\Support\Carbon::parse($row['fecha_venta'])->format('d/m/Y') : '' }}
                        </td>
                        <td class="text-center">
                            @if ($row['fecha_compra'])
                                <a href="{{ route($compraRoute, ['desde' => $row['fecha_compra'], 'hasta' => $row['fecha_compra']]) }}#compra-{{ $row['compra_id'] }}"
                                    class="text-decoration-none" title="Consultar la compra">{{ \Illuminate\Support\Carbon::parse($row['fecha_compra'])->format('d/m/Y') }}</a>
                            @endif
                        </td>
                        <td>{{ $row['factura'] }}</td>
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
                        <td colspan="13" class="text-center text-muted py-3">No hay compras ni ventas registradas.</td>
                    </tr>
                @endforelse
            </tbody>

            <tfoot>
                <tr class="table-secondary fw-bold">
                    <td colspan="4" class="text-end">TOTALES</td>
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
