@php
    use Illuminate\Support\Carbon;

    $combustibles = ['corriente' => 'CORRIENTE', 'acpm' => 'A C P M'];
    $colores = [
        'corriente' => ['bloque' => '#e2efda', 'aforo' => '#d9d9d9'],
        'acpm' => ['bloque' => '#dcefd4', 'aforo' => '#cde8c0'],
    ];
    $galones = fn (?float $valor): string => $valor === null
        ? ''
        : number_format($valor, fmod(round($valor, 2), 1.0) == 0.0 ? 0 : 2, ',', '.');
    $galonesOVacio = fn (?float $valor): string => $valor ? $galones($valor) : '';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Informe de control de tanques</title>
    <style>
        @page { margin: 10mm 8mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #000; }
        h1 { font-size: 14px; color: #d40000; margin: 0 0 2px; }
        .periodo { font-size: 9px; margin-bottom: 6px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #8c8c8c; padding: 2px 3px; }
        th { text-align: center; font-weight: bold; }
        td { text-align: right; }
        .fecha { text-align: center; background: #fff; }
        .bloque { font-size: 11px; }
        .saldos { background: #d6dce4; }
        .saldo { font-weight: bold; }
        .faltante { font-weight: bold; }
        .negativo { color: #ff0000; }
        tfoot td { font-weight: bold; background: #f2f2f2; }
        .nota { margin-top: 6px; font-size: 7px; color: #444; }
    </style>
</head>
<body>
    <h1>INFORME DE CONTROL DE TANQUES</h1>
    <div class="periodo">
        Fecha desde {{ Carbon::parse($desde)->format('d/m/Y') }} — fecha hasta {{ Carbon::parse($hasta)->format('d/m/Y') }}. Solo galones.
    </div>

    <table>
        <thead>
            <tr>
                <th class="fecha"></th>
                @foreach ($combustibles as $combustible => $nombre)
                    <th colspan="6" class="bloque" style="background: {{ $colores[$combustible]['bloque'] }};">{{ $nombre }}</th>
                @endforeach
                <th colspan="2" class="bloque saldos">SALDOS</th>
            </tr>
            <tr>
                <th rowspan="2" class="fecha">FECHA DE<br>VENTA</th>
                @foreach ($combustibles as $combustible => $nombre)
                    <th colspan="3" style="background: {{ $colores[$combustible]['bloque'] }};">MOVIMIENTO DE GALONES</th>
                    <th style="background: {{ $colores[$combustible]['aforo'] }};">AFORO</th>
                    <th style="background: {{ $colores[$combustible]['aforo'] }};">SALDO FINAL</th>
                    <th rowspan="2" style="background: {{ $colores[$combustible]['bloque'] }};">FALTANTE/<br>SOBRANTE</th>
                @endforeach
                <th class="saldos">VR. VENTA</th>
                <th class="saldos">VR. VENTA</th>
            </tr>
            <tr>
                @foreach ($combustibles as $combustible => $nombre)
                    <th style="background: {{ $colores[$combustible]['bloque'] }};">INGRESO</th>
                    <th style="background: {{ $colores[$combustible]['bloque'] }};">VENTA</th>
                    <th style="background: {{ $colores[$combustible]['bloque'] }};">SALDO</th>
                    <th style="background: {{ $colores[$combustible]['aforo'] }};">S/VARA</th>
                    <th style="background: {{ $colores[$combustible]['aforo'] }};">S/TABLA AFORO</th>
                @endforeach
                <th class="saldos">DIARIA</th>
                <th class="saldos">ACUMULADA</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="fecha saldo">VIENEN</td>
                @foreach ($combustibles as $combustible => $nombre)
                    <td style="background: {{ $colores[$combustible]['bloque'] }};"></td>
                    <td style="background: {{ $colores[$combustible]['bloque'] }};"></td>
                    <td class="saldo" style="background: {{ $colores[$combustible]['bloque'] }};">{{ $galones($vienen[$combustible]) }}</td>
                    <td style="background: {{ $colores[$combustible]['aforo'] }};"></td>
                    <td style="background: {{ $colores[$combustible]['aforo'] }};"></td>
                    <td style="background: {{ $colores[$combustible]['bloque'] }};"></td>
                @endforeach
                <td class="saldos"></td>
                <td class="saldos">0</td>
            </tr>
            @foreach ($filas as $fila)
                <tr>
                    <td class="fecha">{{ Carbon::parse($fila['fecha'])->format('d/m/Y') }}</td>
                    @foreach ($combustibles as $combustible => $nombre)
                        @php($dato = $fila[$combustible])
                        <td style="background: {{ $colores[$combustible]['bloque'] }};">{{ $galonesOVacio($dato['ingreso']) }}</td>
                        <td style="background: {{ $colores[$combustible]['bloque'] }};">{{ $galonesOVacio($dato['venta']) }}</td>
                        <td class="saldo" style="background: {{ $colores[$combustible]['bloque'] }};">{{ $galones($dato['saldo']) }}</td>
                        <td style="background: {{ $colores[$combustible]['aforo'] }};">{{ $galones($dato['vara']) }}</td>
                        <td style="background: {{ $colores[$combustible]['aforo'] }};">{{ $galones($dato['tabla']) }}</td>
                        <td class="faltante {{ ($dato['faltante_sobrante'] ?? 0) < 0 ? 'negativo' : '' }}" style="background: {{ $colores[$combustible]['bloque'] }};">@if (($dato['faltante_sobrante'] ?? 0) < 0)FALTANTE @endif{{ $galones($dato['faltante_sobrante']) }}</td>
                    @endforeach
                    <td class="saldos">{{ $galones($fila['venta_diaria']) }}</td>
                    <td class="saldos">{{ $galones($fila['venta_acumulada']) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td class="fecha">TOTALES</td>
                @foreach ($combustibles as $combustible => $nombre)
                    <td>{{ $galones($totales['ingreso_'.$combustible]) }}</td>
                    <td>{{ $galones($totales['venta_'.$combustible]) }}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                @endforeach
                <td>{{ $galones($totales['venta_diaria']) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="nota">
        Ingresos desde compras y ventas según cortes de IAPROPIADA de la planilla de turnos. FALTANTE/SOBRANTE = saldo del
        movimiento de galones − saldo final según tabla de aforo: positivo es sobrante; negativo es FALTANTE (en rojo).
        Generado el {{ now()->format('d/m/Y H:i') }}.
    </div>
</body>
</html>
