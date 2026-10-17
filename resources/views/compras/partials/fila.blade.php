{{--
    Fila de compra de combustible (plantilla con IVA). Parámetros: $index (número o "__INDEX__"
    para la plantilla de filas nuevas), $row (valores digitados) y $proveedores.
    Celdas blancas: las digita el administrador (1). Celdas sombreadas: fórmulas (2), (3) y (4),
    que se ven mientras se digita y el servidor vuelve a calcular al guardar.
--}}
@php($row = $row ?? [])
<tr data-index="{{ $index }}">
    <td>
        <input type="date" name="compras[{{ $index }}][fecha]" class="form-control form-control-sm"
            value="{{ $row['fecha'] ?? '' }}" aria-label="Fecha">
    </td>
    <td>
        <input type="text" name="compras[{{ $index }}][factura]" class="form-control form-control-sm compra-texto"
            value="{{ $row['factura'] ?? '' }}" aria-label="No. factura">
    </td>
    <td>
        <select name="compras[{{ $index }}][proveedor_id]" class="form-select form-select-sm compra-proveedor" aria-label="Proveedor">
            <option value="">Seleccione proveedor</option>
            @foreach ($proveedores as $proveedor)
                <option value="{{ $proveedor->id }}" @selected(($row['proveedor_id'] ?? '') == $proveedor->id)>{{ $proveedor->name }}</option>
            @endforeach
        </select>
    </td>
    @foreach (['gasolina' => 'gasolina', 'acpm' => 'ACPM'] as $combustible => $nombre)
        <td class="compra-inicio-{{ $combustible }}">
            <input type="text" name="compras[{{ $index }}][{{ $combustible }}]" data-campo="{{ $combustible }}"
                class="form-control form-control-sm text-end compra-numero" inputmode="decimal"
                value="{{ $row[$combustible] ?? '' }}" aria-label="Galones {{ $nombre }}">
        </td>
        @foreach (['base19' => 'Base 19%', 'base5' => 'Base 5%'] as $campo => $titulo)
            <td>
                <input type="text" name="compras[{{ $index }}][{{ $campo }}_{{ $combustible }}]" data-campo="{{ $campo }}_{{ $combustible }}"
                    class="form-control form-control-sm text-end compra-numero" inputmode="decimal"
                    value="{{ $row[$campo.'_'.$combustible] ?? '' }}" aria-label="{{ $titulo }} {{ $nombre }}">
            </td>
        @endforeach
        <td class="compra-calc"><output data-calc="iva_{{ $combustible }}">0</output></td>
        @foreach (['costo_producto' => 'Costo producto', 'sobretasa' => 'Sobretasa', 'soldicom' => 'Soldicom'] as $campo => $titulo)
            <td>
                <input type="text" name="compras[{{ $index }}][{{ $campo }}_{{ $combustible }}]" data-campo="{{ $campo }}_{{ $combustible }}"
                    class="form-control form-control-sm text-end compra-numero" inputmode="decimal"
                    value="{{ $row[$campo.'_'.$combustible] ?? '' }}" aria-label="{{ $titulo }} {{ $nombre }}">
            </td>
        @endforeach
        <td class="compra-calc compra-costo"><output data-calc="costo_{{ $combustible }}">0</output></td>
        <td class="compra-calc"><output data-calc="unitario_{{ $combustible }}">0</output></td>
    @endforeach
    <td class="compra-calc compra-costo compra-inicio-total"><output data-calc="vr_total_fra">0</output></td>
    <td class="compra-calc"><output data-calc="total">0</output></td>
    <td class="text-center">
        <button type="button" class="btn btn-sm btn-danger remove-row" aria-label="Quitar fila">×</button>
    </td>
</tr>
