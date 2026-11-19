{{--
    Desprendibles de nómina (hoja NOMINA, bloque "DESPRENDIBLE DE NOMINA DEL MES DE"): uno por empleado,
    dos por hoja carta. Sirve para imprimir en el navegador ($paraPdf = false) y para el PDF (DomPDF).
--}}
@php
    $dinero = fn (float $valor): string => '$ '.number_format($valor, 0, ',', '.');
    $vacio = fn (float $valor): string => round($valor, 2) == 0.0 ? '' : number_format($valor, 0, ',', '.');
    $horas = fn (float $valor): string => $valor > 0 ? rtrim(rtrim(number_format($valor, 2, ',', '.'), '0'), ',') : '';
    $parametros = $liquidacion->parametros;
    $borrador = ! $liquidacion->estaAprobada();
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Desprendibles {{ $liquidacion->nombreDelMes() }}</title>
    <style>
        @page { size: letter; margin: 10mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #111; margin: 0; }
        .acciones { padding: 10px; text-align: center; background: #f1f3f5; }
        .acciones button { font-size: 14px; padding: 6px 16px; cursor: pointer; }
        .desprendible { border: 1px solid #555; padding: 8px 10px; margin: 0 0 10mm; page-break-inside: avoid; position: relative; }
        .desprendible.salto { page-break-after: always; }
        .encabezado { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .encabezado td { vertical-align: top; }
        .empresa { font-size: 12px; font-weight: bold; }
        .titulo { font-size: 11px; font-weight: bold; text-align: right; }
        .borrador { color: #c00000; font-weight: bold; font-size: 11px; text-align: right; }
        table.conceptos { width: 100%; border-collapse: collapse; }
        table.conceptos th, table.conceptos td { border: 1px solid #999; padding: 2px 5px; }
        table.conceptos th { background: #ddebf7; font-size: 9px; }
        table.conceptos td.num { text-align: right; white-space: nowrap; }
        table.conceptos tr.seccion td { background: #1f4e78; color: #fff; font-weight: bold; font-size: 9px; }
        table.conceptos tr.subtotal td { background: #f1f3f5; font-weight: bold; }
        table.conceptos tr.total td { background: #fff2cc; font-weight: bold; font-size: 11px; }
        .firma { width: 100%; margin-top: 18px; border-collapse: collapse; }
        .firma td { width: 50%; vertical-align: bottom; padding: 0 10px; }
        .linea { border-top: 1px solid #111; padding-top: 2px; }
        @media print { .acciones { display: none; } .desprendible { margin-bottom: 8mm; } }
    </style>
</head>
<body>
    @unless ($paraPdf)
        <div class="acciones">
            <button type="button" onclick="window.print()">Imprimir</button>
        </div>
    @endunless

    @foreach ($filas as $fila)
        @php
            $salarioDia = $fila->salario_basico / 30;
            $empleado = $fila->empleado;
        @endphp
        <div class="desprendible {{ $loop->iteration % 2 === 0 && ! $loop->last ? 'salto' : '' }}">
            <table class="encabezado">
                <tr>
                    <td>
                        <div class="empresa">{{ config('nomina.empresa') }}</div>
                        @if (config('nomina.nit'))
                            <div>NIT {{ config('nomina.nit') }}</div>
                        @endif
                        <div>{{ config('nomina.establecimiento') }}</div>
                    </td>
                    <td>
                        <div class="titulo">DESPRENDIBLE DE NÓMINA DEL MES DE {{ $liquidacion->nombreDelMes() }}</div>
                        @if ($borrador)
                            <div class="borrador">BORRADOR — NÓMINA SIN APROBAR</div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="padding-top:4px;">
                        <strong>{{ $empleado->nombre_completo }}</strong> · {{ $empleado->tipo_documento }} {{ $empleado->numero_documento }}
                        · {{ $empleado->cargo }} · Sueldo básico {{ $dinero($fila->salario_basico) }}
                    </td>
                </tr>
            </table>

            <table class="conceptos">
                <thead>
                    <tr>
                        <th style="text-align:left; width:42%;">CONCEPTO</th>
                        <th style="width:10%;">HORAS</th>
                        <th style="width:10%;">DÍAS</th>
                        <th style="width:14%;">VALOR</th>
                        <th>LIQUIDACIÓN</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="seccion"><td colspan="5">DEVENGADOS</td></tr>
                    <tr><td>Básico</td><td></td><td class="num">{{ $fila->dias_trabajados }}</td><td class="num">{{ $vacio($salarioDia) }}</td><td class="num">{{ $vacio($fila->basico) }}</td></tr>
                    <tr><td>Auxilio de transporte</td><td></td><td class="num">{{ $fila->auxilio_transporte ? $fila->dias_trabajados : '' }}</td><td></td><td class="num">{{ $vacio($fila->auxilio_transporte) }}</td></tr>
                    <tr><td>Vacaciones disfrutadas</td><td></td><td class="num">{{ $fila->dias_vacaciones_disfrutadas ?: '' }}</td><td></td><td class="num">{{ $vacio(round($salarioDia * $fila->dias_vacaciones_disfrutadas)) }}</td></tr>
                    <tr><td>Vacaciones pagadas en dinero</td><td></td><td class="num">{{ $fila->dias_vacaciones_dinero ?: '' }}</td><td></td><td class="num">{{ $vacio($fila->vacaciones - round($salarioDia * $fila->dias_vacaciones_disfrutadas)) }}</td></tr>
                    <tr><td>Cesantías</td><td></td><td></td><td></td><td class="num">{{ $vacio($fila->cesantias) }}</td></tr>
                    <tr><td>Intereses s/cesantías</td><td></td><td></td><td></td><td class="num">{{ $vacio($fila->intereses_cesantias) }}</td></tr>
                    <tr><td>Prima legal</td><td></td><td></td><td></td><td class="num">{{ $vacio($fila->prima) }}</td></tr>
                    <tr class="subtotal"><td colspan="4">Subtotal</td><td class="num">{{ $dinero($fila->total_devengado - $fila->totalRecargos()) }}</td></tr>
                    <tr class="seccion"><td colspan="5">SOBRESUELDOS</td></tr>
                    <tr><td>Recargo nocturno</td><td class="num">{{ $horas($fila->horas_nocturnas) }}</td><td></td><td class="num">{{ $parametros && $fila->recargo_nocturno ? $vacio($parametros->valorHoraRecargoNocturno()) : '' }}</td><td class="num">{{ $vacio($fila->recargo_nocturno) }}</td></tr>
                    <tr><td>Recargo dominical y festivo</td><td class="num">{{ $horas($fila->horas_festivas) }}</td><td></td><td class="num">{{ $parametros && $fila->recargo_festivo ? $vacio($parametros->valorHoraRecargoFestivo()) : '' }}</td><td class="num">{{ $vacio($fila->recargo_festivo) }}</td></tr>
                    <tr class="subtotal"><td colspan="4">Subtotal sobresueldos</td><td class="num">{{ $dinero($fila->totalRecargos()) }}</td></tr>
                    <tr class="total"><td colspan="4">TOTAL DEVENGADO</td><td class="num">{{ $dinero($fila->total_devengado) }}</td></tr>
                    <tr class="seccion"><td colspan="5">DEDUCCIONES</td></tr>
                    <tr><td>Salud</td><td></td><td></td><td></td><td class="num">{{ $vacio($fila->salud) }}</td></tr>
                    <tr><td>Pensión</td><td></td><td></td><td></td><td class="num">{{ $vacio($fila->pension) }}</td></tr>
                    <tr><td>Faltantes</td><td></td><td></td><td></td><td class="num">{{ $vacio($fila->faltantes) }}</td></tr>
                    <tr><td>Anticipos</td><td></td><td></td><td></td><td class="num">{{ $vacio($fila->anticipos) }}</td></tr>
                    <tr class="subtotal"><td colspan="4">TOTAL DEDUCCIONES</td><td class="num">{{ $dinero($fila->total_deducciones) }}</td></tr>
                    <tr class="total"><td colspan="4">NETO A PAGAR ({{ $fila->medio_pago }})</td><td class="num">{{ $dinero($fila->neto_pagar) }}</td></tr>
                </tbody>
            </table>

            <table class="firma">
                <tr>
                    <td>He revisado y estoy de acuerdo con la presente liquidación.<br><br><br>
                        <div class="linea">{{ $empleado->nombre_completo }}<br>{{ $empleado->tipo_documento }} {{ $empleado->numero_documento }}</div>
                    </td>
                    <td style="text-align:right; color:#555;">{{ config('nomina.ciudad') }}, {{ $liquidacion->finDelMes()->format('d/m/Y') }}</td>
                </tr>
            </table>
        </div>
    @endforeach
</body>
</html>
