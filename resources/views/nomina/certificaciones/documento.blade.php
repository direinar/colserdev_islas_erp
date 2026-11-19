{{-- Certificación laboral (texto). Se usa en pantalla y en PDF. --}}
@php
    $contratos = \App\Models\Nomina\Empleado::tiposContrato();
    $enLetras = fn (float $valor): string => (new \NumberFormatter('es', \NumberFormatter::SPELLOUT))->format(round($valor));
@endphp
@if ($paraPdf)
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Certificación laboral {{ $empleado->nombre_completo }}</title>
    <style>
        @page { size: letter; margin: 25mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111; line-height: 1.6; }
    </style>
</head>
<body>
@endif
<div class="certificacion">
    <div style="text-align:center; margin-bottom: 28px;">
        <strong style="font-size: 1.15em;">{{ config('nomina.empresa') }}</strong><br>
        @if (config('nomina.nit')) NIT {{ config('nomina.nit') }}<br> @endif
        {{ config('nomina.establecimiento') }}
    </div>
    <p>{{ config('nomina.ciudad') }}, {{ $fecha->locale('es')->translatedFormat('d \d\e F \d\e Y') }}</p>
    <p style="margin-top: 22px;"><strong>{{ mb_strtoupper($dirigidoA) }}</strong></p>
    <p style="text-align:center; margin: 26px 0; font-weight: bold; letter-spacing: .05em;">CERTIFICA QUE:</p>
    <p style="text-align: justify;">
        <strong>{{ mb_strtoupper($empleado->nombre_completo) }}</strong>, identificado(a) con {{ \App\Models\Nomina\Empleado::tiposDocumento()[$empleado->tipo_documento] ?? $empleado->tipo_documento }}
        No. <strong>{{ $empleado->numero_documento }}</strong>,
        @if ($empleado->activo)
            labora en esta empresa desde el {{ $empleado->fecha_ingreso?->locale('es')->translatedFormat('d \d\e F \d\e Y') }},
        @else
            laboró en esta empresa desde el {{ $empleado->fecha_ingreso?->locale('es')->translatedFormat('d \d\e F \d\e Y') }} hasta el {{ $empleado->fecha_retiro?->locale('es')->translatedFormat('d \d\e F \d\e Y') }},
        @endif
        desempeñando el cargo de <strong>{{ mb_strtoupper((string) $empleado->cargo) }}</strong>, con contrato a {{ mb_strtolower($contratos[$empleado->tipo_contrato] ?? (string) $empleado->tipo_contrato) }}@if ($incluirSalario)
            y un salario básico mensual de <strong>${{ number_format((float) $empleado->salario_basico, 0, ',', '.') }}</strong>
            ({{ mb_strtoupper($enLetras((float) $empleado->salario_basico)) }} PESOS M/CTE)@endif.
    </p>
    <p>La presente se expide a solicitud del interesado(a).</p>
    <div style="margin-top: 70px; width: 60%; border-top: 1px solid #111; padding-top: 4px;">
        {{ config('nomina.firma_nombre') ?: 'Firma autorizada' }}<br>{{ config('nomina.firma_cargo') }}<br>{{ config('nomina.empresa') }}
        @if (config('nomina.telefono'))<br>Tel. {{ config('nomina.telefono') }}@endif
    </div>
</div>
@if ($paraPdf)
</body>
</html>
@endif
