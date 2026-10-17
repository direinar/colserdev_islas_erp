{{--
    Encabezado de las tablas de compras de combustible (plantilla con IVA): tres filas como en
    el Excel. Los números entre paréntesis remiten a la leyenda de la pantalla.
--}}
@php($accionImprimible = $accionImprimible ?? true)
<tr>
    <th rowspan="3" class="text-center align-middle">FECHA</th>
    <th rowspan="3" class="text-center align-middle">No. FACTURA</th>
    <th rowspan="3" class="text-center align-middle">PROVEEDOR</th>
    <th colspan="9" class="text-center compra-grupo-gasolina compra-inicio-gasolina">GASOLINA</th>
    <th colspan="9" class="text-center compra-grupo-acpm compra-inicio-acpm">ACPM</th>
    <th colspan="2" class="text-center compra-grupo-total compra-inicio-total">TOTAL</th>
    <th rowspan="3" class="text-center align-middle {{ $accionImprimible ? '' : 'd-print-none' }}">ACCIÓN</th>
</tr>
<tr>
    @foreach (['gasolina' => 'GASOLINA', 'acpm' => 'ACPM'] as $combustible => $nombre)
        <th rowspan="2" class="text-center align-middle compra-sub-{{ $combustible }} compra-inicio-{{ $combustible }}">GALONES</th>
        <th colspan="2" class="text-center compra-sub-{{ $combustible }}">BASE <span class="compra-ref">(1)</span></th>
        <th rowspan="2" class="text-center align-middle compra-sub-{{ $combustible }} compra-th-calc">LIQUIDACIÓN<br>IVA <span class="compra-ref">(2)</span></th>
        <th rowspan="2" class="text-center align-middle compra-sub-{{ $combustible }}">COSTO<br>PRODUCTO <span class="compra-ref">(1)</span></th>
        <th rowspan="2" class="text-center align-middle compra-sub-{{ $combustible }}">SOBRETASA <span class="compra-ref">(1)</span></th>
        <th rowspan="2" class="text-center align-middle compra-sub-{{ $combustible }}">SOLDICOM <span class="compra-ref">(1)</span></th>
        <th rowspan="2" class="text-center align-middle compra-sub-{{ $combustible }} compra-th-calc">COSTO<br>{{ $nombre }} <span class="compra-ref">(3)</span></th>
        <th rowspan="2" class="text-center align-middle compra-sub-{{ $combustible }} compra-th-calc">COSTO<br>UNITARIO <span class="compra-ref">(4)</span></th>
    @endforeach
    <th rowspan="2" class="text-center align-middle compra-sub-total compra-inicio-total">VALOR</th>
    <th rowspan="2" class="text-center align-middle compra-sub-total">GALONES</th>
</tr>
<tr>
    @foreach (['gasolina', 'acpm'] as $combustible)
        <th class="text-center compra-sub-{{ $combustible }}">19%</th>
        <th class="text-center compra-sub-{{ $combustible }}">5%</th>
    @endforeach
</tr>
