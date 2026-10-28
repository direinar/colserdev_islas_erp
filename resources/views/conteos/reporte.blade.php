@php
    use App\Models\ConteoMovimiento;

    $pesos = fn ($valor): string => number_format((float) $valor, 0, ',', '.');
    $rojo = fn ($valor): string => round((float) $valor) < 0 ? 'negativo' : '';
    $movimientos = fn (string $tipo) => $conteo->movimientos->where('tipo', $tipo);
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Conteo No. {{ $conteo->numero }}</title>
    <style>
        @page { margin: 10mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; color: #000; margin: 0; padding: 8px; }
        h1 { font-size: 15px; margin: 0; letter-spacing: 3px; }
        .encabezado { width: 100%; margin-bottom: 8px; }
        .encabezado td { border: 0; font-size: 11px; font-weight: bold; }
        h2 { font-size: 10px; background: #eeece1; padding: 3px 4px; margin: 10px 0 0; border: 1px solid #8c8c8c; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #8c8c8c; padding: 2px 4px; }
        th { background: #f2f2f2; text-align: center; }
        td.valor { text-align: right; white-space: nowrap; }
        tr.total td { font-weight: bold; background: #f2f2f2; }
        .negativo { color: #ff0000; }
        .sin-planilla { color: #ff0000; }
        .nota { margin-top: 8px; font-size: 8px; color: #444; }
        .imprimir { margin-bottom: 10px; }

        @media print {
            .imprimir { display: none; }
        }
    </style>
</head>
<body>
    @unless ($paraPdf)
        <div class="imprimir"><button type="button" onclick="window.print()">IMPRIMIR</button></div>
    @endunless

    <table class="encabezado">
        <tr>
            <td><h1>C O N T E O</h1></td>
            <td>FECHA: {{ $conteo->fecha->format('d/m/Y') }}</td>
            <td>No. {{ $conteo->numero }}</td>
        </tr>
    </table>

    <h2>CUADRE DE EFECTIVO</h2>
    <table>
        <tr><th>BILLETE</th><th>VALOR</th></tr>
        @foreach ($conteo->billetes as $billete)
            <tr><td>{{ $billete->denominacion }}</td><td class="valor">{{ $pesos($billete->valor) }}</td></tr>
        @endforeach
        <tr class="total"><td>BILLETES</td><td class="valor">{{ $pesos($resumen['billetes']) }}</td></tr>
        <tr><td>MONEDA</td><td class="valor">{{ $pesos($resumen['moneda']) }}</td></tr>
        <tr class="total"><td>SUB TOTAL</td><td class="valor">{{ $pesos($resumen['subtotal']) }}</td></tr>
        <tr><td>RETIROS</td><td class="valor">{{ $pesos($resumen['retiros']) }}</td></tr>
        <tr class="total"><td>TOTAL CIERRE</td><td class="valor">{{ $pesos($resumen['total_cierre']) }}</td></tr>
    </table>

    <h2>RETIROS ANTES DE ESTE CIERRE</h2>
    <table>
        <tr><th>NOMBRE</th><th>CONCEPTO</th><th>VALOR</th></tr>
        @foreach ($movimientos(ConteoMovimiento::TIPO_RETIRO) as $retiro)
            <tr><td>{{ $retiro->nombre }}</td><td>{{ $retiro->concepto }}</td><td class="valor">{{ $pesos($retiro->valor) }}</td></tr>
        @endforeach
        <tr class="total"><td colspan="2">TOTAL RETIROS</td><td class="valor">{{ $pesos($resumen['retiros']) }}</td></tr>
    </table>

    <h2>DETALLE DE NUMERACIÓN DE CONSIGNACIONES DE COMBUSTIBLE</h2>
    <table>
        <tr><th>No.</th><th>VALOR</th><th>PLANILLA</th></tr>
        <tr><td>{{ $resumen['consignacion_anterior'] }}</td><td colspan="2">Último No. del conteo anterior</td></tr>
        @foreach ($conteo->consignaciones as $consignacion)
            <tr>
                <td>{{ $consignacion->numero }}</td>
                <td class="valor">{{ $pesos($consignacion->valor) }}</td>
                <td @class(['sin-planilla' => ! $consignacion->turno])>
                    {{ $consignacion->turno ? 'Turno #'.$consignacion->turno->numero_turno.' — '.$consignacion->turno->fecha->format('d/m/Y') : 'No está en ninguna planilla' }}
                </td>
            </tr>
        @endforeach
        <tr class="total"><td>TOTAL CONSIGNACIONES INTERNAS</td><td class="valor">{{ $pesos($resumen['total_consignaciones']) }}</td><td></td></tr>
        <tr class="total"><td>DIFERENCIA (TOTAL CIERRE − CONSIGNACIONES)</td><td class="valor {{ $rojo($resumen['diferencia']) }}">{{ $pesos($resumen['diferencia']) }}</td><td></td></tr>
    </table>

    <h2>CUADRE DE ESTE CONTEO</h2>
    <table>
        <tr><th>CONCEPTO (GASTOS)</th><th>VALOR</th></tr>
        @foreach ($movimientos(ConteoMovimiento::TIPO_GASTO) as $gasto)
            <tr><td>{{ $gasto->concepto }}</td><td class="valor">{{ $pesos($gasto->valor) }}</td></tr>
        @endforeach
        <tr><td>TOTAL BILLETES</td><td class="valor">{{ $pesos($resumen['billetes']) }}</td></tr>
        <tr><td>TOTAL GASTOS</td><td class="valor">{{ $pesos($resumen['gastos']) }}</td></tr>
        <tr class="total"><td>A CONSIGNAR</td><td class="valor {{ $rojo($resumen['a_consignar']) }}">{{ $pesos($resumen['a_consignar']) }}</td></tr>
    </table>

    <h2>CONSIGNACIÓN EN BANCOS Y OTRAS APLICACIONES</h2>
    <table>
        <tr><th>DESTINO</th><th>BANCO / TERCERO</th><th>VALOR</th></tr>
        @foreach ($conteo->aplicaciones as $aplicacion)
            <tr><td>{{ $aplicacion->destino?->nombre }}</td><td>{{ $aplicacion->tercero?->nombre }}</td><td class="valor">{{ $pesos($aplicacion->valor) }}</td></tr>
        @endforeach
        <tr class="total"><td colspan="2">TOTAL CONSIGNACIONES Y OTROS</td><td class="valor">{{ $pesos($resumen['aplicaciones']) }}</td></tr>
        <tr class="total"><td colspan="2">DIFERENCIA NO CONSIGNADA</td><td class="valor {{ $rojo($resumen['no_consignado']) }}">{{ $pesos($resumen['no_consignado']) }}</td></tr>
    </table>

    <h2>CONTROL DE MONEDA</h2>
    <table>
        <tr><th>RETIROS DE MONEDA (CONCEPTO)</th><th>VALOR</th></tr>
        @foreach ($movimientos(ConteoMovimiento::TIPO_RETIRO_MONEDA) as $retiro)
            <tr><td>{{ $retiro->concepto }}</td><td class="valor">{{ $pesos($retiro->valor) }}</td></tr>
        @endforeach
        <tr><td>Saldo que viene del conteo anterior</td><td class="valor">{{ $pesos($resumen['saldo_moneda_anterior']) }}</td></tr>
        <tr><td>Más: moneda del presente conteo</td><td class="valor">{{ $pesos($resumen['moneda']) }}</td></tr>
        <tr><td>Menos: retiros</td><td class="valor">{{ $pesos($resumen['retiros_moneda']) }}</td></tr>
        <tr class="total"><td>Saldo que pasa</td><td class="valor {{ $rojo($resumen['saldo_moneda_pasa']) }}">{{ $pesos($resumen['saldo_moneda_pasa']) }}</td></tr>
    </table>

    <div class="nota">Registró: {{ $conteo->user?->name }}. Generado el {{ now()->format('d/m/Y H:i') }}.</div>
</body>
</html>
