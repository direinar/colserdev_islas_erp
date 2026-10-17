{{--
    Encabezado de las tablas de compras de combustible (plantilla con IVA): tres filas como en
    el Excel.
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
        <th colspan="2" class="text-center compra-sub-{{ $combustible }}">BASE</th>
        <th rowspan="2" class="text-center align-middle compra-sub-{{ $combustible }} compra-th-calc">LIQUIDACIÓN<br>IVA</th>
        <th rowspan="2" class="text-center align-middle compra-sub-{{ $combustible }}">COSTO<br>PRODUCTO</th>
        <th rowspan="2" class="text-center align-middle compra-sub-{{ $combustible }}">SOBRETASA</th>
        <th rowspan="2" class="text-center align-middle compra-sub-{{ $combustible }}">SOLDICOM</th>
        <th rowspan="2" class="text-center align-middle compra-sub-{{ $combustible }} compra-th-calc">COSTO<br>{{ $nombre }}</th>
        <th rowspan="2" class="text-center align-middle compra-sub-{{ $combustible }} compra-th-calc">COSTO<br>UNITARIO</th>
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
