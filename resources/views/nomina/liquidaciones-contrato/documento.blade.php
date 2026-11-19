{{-- Documento de liquidación del contrato (hoja LIQUIDADOR PREST. SOC.). Se usa en pantalla y en PDF. --}}
@php
    $dinero = fn (float $valor): string => '$ '.number_format($valor, 0, ',', '.');
    $empleado = $liquidacion->empleado;
@endphp
@unless ($paraPdf)
    <div class="liq-documento">
@else
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Liquidación {{ $empleado->nombre_completo }}</title>
    <style>
        @page { size: letter; margin: 15mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10.5px; color: #111; }
    </style>
</head>
<body>
    <div class="liq-documento">
@endunless
        <style>
            .liq-documento table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
            .liq-documento th, .liq-documento td { border: 1px solid #999; padding: 3px 6px; }
            .liq-documento th { background: #ddebf7; text-align: left; }
            .liq-documento td.num { text-align: right; white-space: nowrap; }
            .liq-documento tr.subtotal td { background: #f1f3f5; font-weight: bold; }
            .liq-documento tr.total td { background: #fff2cc; font-weight: bold; font-size: 1.1em; }
            .liq-documento .titulo { text-align: center; font-weight: bold; font-size: 1.2em; margin: 4px 0 8px; }
            .liq-documento .firmas { width: 100%; margin-top: 45px; }
            .liq-documento .firmas td { border: 0; width: 50%; padding: 0 12px; vertical-align: top; }
            .liq-documento .linea { border-top: 1px solid #111; padding-top: 3px; }
        </style>
        <div style="text-align:center;">
            <strong>{{ config('nomina.empresa') }}</strong>@if (config('nomina.nit')) · NIT {{ config('nomina.nit') }}@endif<br>{{ config('nomina.establecimiento') }}
        </div>
        <div class="titulo">LIQUIDACIÓN CONTRATO DE TRABAJO Y/O PRESTACIONES SOCIALES</div>

        <table>
            <tr><th style="width:42%;">NOMBRE DEL EMPLEADO</th><td>{{ $empleado->nombre_completo }} · {{ $empleado->tipo_documento }} {{ $empleado->numero_documento }}</td></tr>
            <tr><th>CARGO</th><td>{{ $empleado->cargo }}</td></tr>
            <tr><th>MOTIVO DEL RETIRO</th><td>{{ $liquidacion->motivo_retiro }}</td></tr>
            <tr><th>FECHA DE INGRESO</th><td>{{ $liquidacion->fecha_ingreso->format('d/m/Y') }}</td></tr>
            <tr><th>FECHA DE RETIRO</th><td>{{ $liquidacion->fecha_retiro->format('d/m/Y') }}</td></tr>
            <tr><th>DÍAS TRABAJADOS EN ESTE AÑO</th><td>{{ $liquidacion->dias_anio }}</td></tr>
            <tr><th>DÍAS TRABAJADOS EN ESTE SEMESTRE</th><td>{{ $liquidacion->dias_semestre }}</td></tr>
            <tr><th>TOTAL DÍAS TRABAJADOS DURANTE EL CONTRATO</th><td>{{ number_format($liquidacion->dias_contrato, 0, ',', '.') }}</td></tr>
            <tr><th>SALARIO BASE DE LIQUIDACIÓN</th><td>{{ $dinero($liquidacion->salario_base) }}</td></tr>
        </table>

        <table>
            <thead><tr><th>CONCEPTO</th><th style="text-align:right;">VALOR</th></tr></thead>
            <tbody>
                <tr><td>Sueldo pendiente</td><td class="num">{{ $dinero($liquidacion->sueldo_pendiente) }}</td></tr>
                <tr><td>Recargos nocturnos</td><td class="num">{{ $dinero($liquidacion->recargos_nocturnos) }}</td></tr>
                <tr><td>Recargos por festivos</td><td class="num">{{ $dinero($liquidacion->recargos_festivos) }}</td></tr>
                <tr><td>Auxilio de transporte</td><td class="num">{{ $dinero($liquidacion->auxilio_transporte) }}</td></tr>
                <tr class="subtotal"><td>Subtotal salarios</td><td class="num">{{ $dinero($liquidacion->subtotalSalarios()) }}</td></tr>
                <tr><td>Cesantías (base ÷ 360 × días del año)</td><td class="num">{{ $dinero($liquidacion->cesantias) }}</td></tr>
                <tr><td>Intereses sobre cesantías (12 % anual)</td><td class="num">{{ $dinero($liquidacion->intereses_cesantias) }}</td></tr>
                <tr><td>Prima de servicios (base ÷ 360 × días del semestre)</td><td class="num">{{ $dinero($liquidacion->prima) }}</td></tr>
                <tr><td>Vacaciones pendientes (base ÷ 720 × días del año)</td><td class="num">{{ $dinero($liquidacion->vacaciones) }}</td></tr>
                <tr class="subtotal"><td>Subtotal prestaciones</td><td class="num">{{ $dinero($liquidacion->subtotalPrestaciones()) }}</td></tr>
                <tr><td>Deducción EPS</td><td class="num">{{ $dinero($liquidacion->salud) }}</td></tr>
                <tr><td>Deducción pensiones</td><td class="num">{{ $dinero($liquidacion->pension) }}</td></tr>
                <tr><td>Otras deducciones (faltantes, anticipos)</td><td class="num">{{ $dinero($liquidacion->otras_deducciones) }}</td></tr>
                <tr class="subtotal"><td>Total deducciones</td><td class="num">{{ $dinero($liquidacion->totalDeducciones()) }}</td></tr>
                <tr class="total"><td>TOTAL NETO A PAGAR</td><td class="num">{{ $dinero($liquidacion->neto_pagar) }}</td></tr>
            </tbody>
        </table>

        @if ($liquidacion->observaciones)
            <p><strong>Observaciones:</strong> {{ $liquidacion->observaciones }}</p>
        @endif

        <p>Estoy de acuerdo con la presente liquidación y declaro haber recibido todos los salarios y prestaciones sociales a que tenía derecho.</p>

        <table class="firmas">
            <tr>
                <td><div class="linea">{{ $empleado->nombre_completo }}<br>{{ $empleado->tipo_documento }} No. {{ $empleado->numero_documento }}</div></td>
                <td><div class="linea">{{ config('nomina.firma_nombre') ?: 'Por la empresa' }}<br>{{ config('nomina.firma_cargo') }}</div></td>
            </tr>
        </table>
@unless ($paraPdf)
    </div>
@else
    </div>
</body>
</html>
@endunless
