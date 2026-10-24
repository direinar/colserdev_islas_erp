@php
    use Illuminate\Support\Carbon;

    $money = fn ($value) => number_format((float) $value, 0, ',', '.');
    $moneyOVacio = fn ($value) => (float) $value != 0 ? $money($value) : '';
    $galones = fn ($value) => (float) $value != 0 ? rtrim(rtrim(number_format((float) $value, 3, ',', '.'), '0'), ',') : '';
    $saldoClass = fn ($value) => (float) $value < 0 ? 'negativo' : '';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Estado de cuenta</title>
    <style>
        @page { margin: 10mm 8mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #000; }
        h1 { font-size: 14px; margin: 0 0 2px; }
        .cliente { font-size: 10px; font-weight: bold; }
        .periodo { font-size: 9px; margin-bottom: 6px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #8c8c8c; padding: 2px 3px; }
        th { text-align: center; font-weight: bold; background: #ccccff; }
        td { text-align: right; }
        .texto { text-align: left; }
        .centro { text-align: center; }
        .saldo-anterior td, tfoot td { font-weight: bold; background: #f2f2f2; }
        .negativo { color: #ff0000; font-weight: bold; }
        .nota { margin-top: 6px; font-size: 7px; color: #444; }
    </style>
</head>
<body>
    <h1>ESTADO DE CUENTA</h1>
    <div class="cliente">
        {{ $customer->name }} — NIT/C.C. {{ $customer->document }}{{ $customer->dv !== null && $customer->dv !== '' ? '-'.$customer->dv : '' }}
    </div>
    <div class="periodo">
        Del {{ Carbon::parse($fechaInicial)->format('d/m/Y') }} al {{ Carbon::parse($fechaFinal)->format('d/m/Y') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>PLANILLA</th>
                <th>FECHA</th>
                <th>FACTURA</th>
                <th>PLACAS</th>
                <th>PRODUCTO</th>
                <th>GALONES</th>
                <th>VR. UNITARIO</th>
                <th>VR. BRUTO</th>
                <th>DESCUENTO</th>
                <th>CONCEPTO</th>
                <th>VR. NETO CARGO</th>
                <th>ABONOS</th>
                <th>SALDO</th>
            </tr>
        </thead>
        <tbody>
            <tr class="saldo-anterior">
                <td colspan="12" class="texto">SALDO ANTERIOR al {{ Carbon::parse($fechaInicial)->subDay()->format('d/m/Y') }}</td>
                <td class="{{ $saldoClass($saldoAnterior) }}">{{ $money($saldoAnterior) }}</td>
            </tr>
            @forelse ($movimientos as $m)
                <tr>
                    <td class="centro">{{ $m->esDeTurno() ? $m->planillas : '' }}</td>
                    <td class="centro">{{ $m->fecha?->format('d/m/Y') }}</td>
                    <td class="texto">{{ $m->factura }}</td>
                    <td class="texto">{{ $m->llevaDatosVehiculo() ? $m->placas : '' }}</td>
                    <td class="texto">{{ $m->llevaDatosVehiculo() ? $m->producto : '' }}</td>
                    <td>{{ $m->llevaDatosVehiculo() ? $galones($m->galones) : '' }}</td>
                    <td>{{ $m->llevaDatosVehiculo() ? $moneyOVacio($m->vr_unitario) : '' }}</td>
                    <td>{{ $m->llevaDatosVehiculo() ? $moneyOVacio($m->bruto) : '' }}</td>
                    <td>{{ $m->llevaDatosVehiculo() ? $moneyOVacio($m->descuento) : '' }}</td>
                    <td class="texto">{{ $m->concepto }}</td>
                    <td>{{ $moneyOVacio($m->vr_neto_cargo) }}</td>
                    <td>{{ $moneyOVacio($m->abonos) }}</td>
                    <td class="{{ $saldoClass($m->saldo_corrido) }}">{{ $money($m->saldo_corrido) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="13" class="centro">El cliente no tiene movimientos entre las fechas seleccionadas.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="10">TOTALES</td>
                <td>{{ $money($totalCargos) }}</td>
                <td>{{ $money($totalAbonos) }}</td>
                <td class="{{ $saldoClass($saldoFinal) }}">{{ $money($saldoFinal) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="nota">
        Saldo negativo (en rojo): saldo a favor del cliente. Generado el {{ now()->format('d/m/Y H:i') }}.
    </div>
</body>
</html>
