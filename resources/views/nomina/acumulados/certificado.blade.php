{{-- Certificado de ingresos laborales del año (acumulado de nóminas aprobadas). Imprimir o PDF. --}}
@php
    $dinero = fn (float $valor): string => '$ '.number_format($valor, 0, ',', '.');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificado de ingresos {{ $anio }} - {{ $empleado->nombre_completo }}</title>
    <style>
        @page { size: letter; margin: 18mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #111; margin: 0; }
        .acciones { padding: 10px; text-align: center; background: #f1f3f5; }
        .acciones button { font-size: 14px; padding: 6px 16px; cursor: pointer; }
        .hoja { max-width: 190mm; margin: 0 auto; padding: 10px; }
        h1 { font-size: 15px; text-align: center; margin: 4px 0; }
        .centro { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #999; padding: 4px 6px; }
        th { background: #ddebf7; text-align: left; }
        td.num { text-align: right; white-space: nowrap; }
        tr.total td { background: #fff2cc; font-weight: bold; }
        .firma { margin-top: 50px; width: 60%; border-top: 1px solid #111; padding-top: 3px; }
        @media print { .acciones { display: none; } }
    </style>
</head>
<body>
    @unless ($paraPdf)
        <div class="acciones"><button type="button" onclick="window.print()">Imprimir</button></div>
    @endunless
    <div class="hoja">
        <div class="centro">
            <strong style="font-size:13px;">{{ config('nomina.empresa') }}</strong><br>
            @if (config('nomina.nit')) NIT {{ config('nomina.nit') }}<br> @endif
            {{ config('nomina.establecimiento') }}
        </div>
        <h1>CERTIFICADO DE INGRESOS LABORALES AÑO GRAVABLE {{ $anio }}</h1>

        <table>
            <tr><th style="width:35%;">Trabajador</th><td>{{ $empleado->nombre_completo }}</td></tr>
            <tr><th>Identificación</th><td>{{ $empleado->tipo_documento }} {{ $empleado->numero_documento }}</td></tr>
            <tr><th>Cargo</th><td>{{ $empleado->cargo }}</td></tr>
            <tr><th>Periodo certificado</th><td>Nóminas de {{ $meses->map(fn ($fila) => ucfirst($fila->liquidacion->inicioDelMes()->locale('es')->monthName))->implode(', ') }} de {{ $anio }}</td></tr>
        </table>

        <table>
            <thead><tr><th>CONCEPTO</th><th style="text-align:right;">VALOR</th></tr></thead>
            <tbody>
                <tr><td>Salarios (sueldo básico)</td><td class="num">{{ $dinero($acumulado['sueldo']) }}</td></tr>
                <tr><td>Sobresueldos (recargos nocturnos y festivos)</td><td class="num">{{ $dinero($acumulado['sobresueldos']) }}</td></tr>
                <tr><td>Auxilio de transporte</td><td class="num">{{ $dinero($acumulado['auxilio']) }}</td></tr>
                <tr><td>Cesantías pagadas</td><td class="num">{{ $dinero($acumulado['cesantias']) }}</td></tr>
                <tr><td>Intereses sobre cesantías</td><td class="num">{{ $dinero($acumulado['intereses']) }}</td></tr>
                <tr><td>Vacaciones</td><td class="num">{{ $dinero($acumulado['vacaciones']) }}</td></tr>
                <tr><td>Prima legal de servicios</td><td class="num">{{ $dinero($acumulado['prima']) }}</td></tr>
                <tr class="total"><td>TOTAL INGRESOS LABORALES</td><td class="num">{{ $dinero($acumulado['total_ingresos']) }}</td></tr>
                <tr><td>Aportes obligatorios a salud (trabajador)</td><td class="num">{{ $dinero($acumulado['salud']) }}</td></tr>
                <tr><td>Aportes obligatorios a pensión (trabajador)</td><td class="num">{{ $dinero($acumulado['pension']) }}</td></tr>
                <tr class="total"><td>TOTAL APORTES DEL TRABAJADOR</td><td class="num">{{ $dinero($acumulado['total_descuentos']) }}</td></tr>
            </tbody>
        </table>

        <p style="margin-top:14px;">Se expide en {{ config('nomina.ciudad') }} el {{ now()->locale('es')->translatedFormat('d \d\e F \d\e Y') }}, con base en las nóminas aprobadas del año.</p>

        <div class="firma">
            {{ config('nomina.firma_nombre') ?: 'Firma autorizada' }}<br>{{ config('nomina.firma_cargo') }}
        </div>
    </div>
</body>
</html>
