@php
    use Illuminate\Support\Carbon;

    $pesos = fn ($valor): string => number_format((float) $valor, 0, ',', '.');
    $estadistica = fn ($valor, string $formato): string => $valor === null
        ? ''
        : ($formato === '0.00%' ? number_format((float) $valor * 100, 2, ',', '.').'%' : number_format((float) $valor, 0, ',', '.'));
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Informes de gestión</title>
    <style>
        @page { margin: 12mm 10mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #000; }
        h1 { font-size: 14px; color: #1f4e78; margin: 0 0 2px; }
        .periodo { font-size: 10px; margin-bottom: 8px; }
        h2 { font-size: 10px; margin: 12px 0 4px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #8c8c8c; padding: 3px 4px; }
        th { background: #1f4e78; color: #fff; text-align: center; }
        td.valor { text-align: right; white-space: nowrap; }
        td.ref { text-align: center; }
        tr.destacado td { background: #fff2cc; font-weight: bold; }
        .negativo { color: #ff0000; }
        .nota { margin-top: 8px; font-size: 7px; color: #444; }
    </style>
</head>
<body>
    <h1>INFORMES DE GESTIÓN</h1>
    <div class="periodo">Desde {{ Carbon::parse($desde)->format('d/m/Y') }} hasta {{ Carbon::parse($hasta)->format('d/m/Y') }}</div>

    <table>
        <thead>
            <tr><th>CONCEPTO</th><th>VALOR</th><th>REF.</th><th>DE DÓNDE SE TOMA LA INFORMACIÓN</th></tr>
        </thead>
        <tbody>
            @foreach ($renglones as [$concepto, $valor, $referencia, $origen, $destacado])
                <tr @class(['destacado' => $destacado])>
                    <td>{{ $concepto }}</td>
                    <td class="valor {{ round((float) $valor) < 0 ? 'negativo' : '' }}">{{ $pesos($valor) }}</td>
                    <td class="ref">{{ $referencia }}</td>
                    <td>{{ $origen }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>ESTADÍSTICAS DEL PERIODO</h2>
    <table>
        <tbody>
            @foreach ($estadisticas as [$concepto, $valor, $referencia, $origen, $formato])
                <tr>
                    <td>{{ $concepto }}</td>
                    <td class="valor">{{ $estadistica($valor, $formato) }}</td>
                    <td class="ref">{{ $referencia }}</td>
                    <td>{{ $origen }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="nota">Generado el {{ now()->format('d/m/Y H:i') }}.</div>
</body>
</html>
