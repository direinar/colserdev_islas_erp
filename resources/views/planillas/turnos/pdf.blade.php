@php
    $formatear = fn ($valor, string $formato): string => match (true) {
        $formato === 'texto' || ! is_numeric($valor) => (string) $valor,
        $formato === 'galones' => number_format((float) $valor, 3, ',', '.'),
        default => number_format((float) $valor, 0, ',', '.'),
    };
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Planilla de turno {{ $turno->numero_turno }}</title>
    <style>
        @page { margin: 10mm 10mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #000; }
        h1 { font-size: 14px; color: #c00000; margin: 0 0 6px; }
        h2 { font-size: 10px; margin: 10px 0 3px; background: #d9d6f9; padding: 3px 4px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #8c8c8c; padding: 2px 4px; }
        th { background: #eeedfc; text-align: center; }
        td.valor { text-align: right; white-space: nowrap; }
        tr.total td { font-weight: bold; background: #e8e8e8; }
        .encabezado td { border: none; padding: 1px 4px; }
        .encabezado td.campo { font-weight: bold; width: 110px; }
        .negativo { color: #ff0000; }
        .anulada { color: #c00000; font-size: 16px; font-weight: bold; letter-spacing: 4px; }
    </style>
</head>
<body>
    <h1>PLANILLA DE TURNO {{ str_pad((string) $turno->numero_turno, 3, '0', STR_PAD_LEFT) }}</h1>
    @if ($turno->anulada)
        <div class="anulada">A N U L A D A</div>
    @endif

    <table class="encabezado">
        @foreach ($encabezado as [$campo, $valor])
            <tr><td class="campo">{{ $campo }}</td><td>{{ $valor }}</td></tr>
        @endforeach
    </table>

    @foreach ($secciones as $seccion)
        <h2>{{ $seccion['titulo'] }}</h2>
        <table>
            <thead>
                <tr>
                    @foreach ($seccion['columnas'] as $columna)
                        <th>{{ $columna['titulo'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($seccion['filas'] as $fila)
                    <tr>
                        @foreach (array_values($fila) as $indice => $valor)
                            @php($formato = $seccion['columnas'][$indice]['formato'])
                            <td @class(['valor' => $formato !== 'texto', 'negativo' => $formato !== 'texto' && is_numeric($valor) && round((float) $valor) < 0])>{{ $formatear($valor, $formato) }}</td>
                        @endforeach
                    </tr>
                @endforeach
                @if ($seccion['total'] !== null)
                    <tr class="total">
                        @foreach (array_values($seccion['total']) as $indice => $valor)
                            <td @class(['valor' => $seccion['columnas'][$indice]['formato'] !== 'texto'])>{{ $formatear($valor, $seccion['columnas'][$indice]['formato']) }}</td>
                        @endforeach
                    </tr>
                @endif
            </tbody>
        </table>
    @endforeach
</body>
</html>
