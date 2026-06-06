<div class="row">

    <div class="col-md-6 mb-3">

        <label class="form-label">
            Referencia
        </label>

        <input type="text"
               name="reference"
               class="form-control"
               value="{{ old('reference', $lubricant->reference ?? '') }}"
               required>

    </div>

    <div class="col-md-3 mb-3">

        <label class="form-label">
            Precio Venta
        </label>

        <input type="number"
               step="0.01"
               name="sale_price"
               id="sale_price"
               class="form-control"
               value="{{ old('sale_price', $lubricant->sale_price ?? '') }}"
               required>

    </div>

    <div class="col-md-3 mb-3">

        <label class="form-label">
            IVA
        </label>

        <input type="number"
               step="0.01"
               name="iva"
               id="iva"
               class="form-control"
               value="{{ old('iva', $lubricant->iva ?? 0) }}"
               required>

    </div>

</div>

<div class="row">

    <div class="col-md-4 mb-3">

        <label class="form-label">
            Total
        </label>

        <input type="number"
               step="0.01"
               name="total"
               id="total"
               class="form-control"
               value="{{ old('total', $lubricant->total ?? '') }}"
               readonly>

    </div>

    <div class="col-md-4 mb-3">

        <label class="form-label">
            Costo
        </label>

        <input type="number"
               step="0.01"
               name="cost_price"
               class="form-control"
               value="{{ old('cost_price', $lubricant->cost_price ?? '') }}"
               required>

    </div>

    <div class="col-md-4 mb-3">

        <label class="form-label">
            Proveedor
        </label>

        <input type="text"
               name="supplier"
               class="form-control"
               value="{{ old('supplier', $lubricant->supplier ?? '') }}">

    </div>

</div>

<div class="mb-3">

    <label class="form-label">
        Estado
    </label>

    <select name="active"
            class="form-select">

        <option value="1">
            Activo
        </option>

        <option value="0">
            Inactivo
        </option>

    </select>

</div>

<script>
    function calcularTotal() {

        let venta =
            parseFloat(document.getElementById('sale_price').value) || 0;

        let iva =
            parseFloat(document.getElementById('iva').value) || 0;

        document.getElementById('total').value =
            (venta + iva).toFixed(2);
    }

    document.getElementById('sale_price')
        ?.addEventListener('input', calcularTotal);

    document.getElementById('iva')
        ?.addEventListener('input', calcularTotal);
</script>
