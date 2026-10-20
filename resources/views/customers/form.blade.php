<div class="row">

    <div class="col-md-6 mb-3">
        <label for="campo-name" class="form-label">Nombre</label>
        <input id="campo-name" type="text" name="name" class="form-control" value="{{ old('name', $customer->name ?? '') }}" required>
    </div>

    <div class="col-md-4 mb-3">
        <label for="campo-document" class="form-label">Documento</label>
        <input id="campo-document" type="text" name="document" class="form-control"
            value="{{ old('document', $customer->document ?? '') }}" required>
    </div>

    <div class="col-md-2 mb-3">
        <label for="campo-dv" class="form-label"><abbr title="Dígito de verificación">D.V.</abbr></label>
        <input id="campo-dv" type="text" name="dv" class="form-control" inputmode="numeric" maxlength="1"
            value="{{ old('dv', $customer->dv ?? '') }}" placeholder="Auto">
        <div class="form-text">Vacío: se calcula del NIT.</div>
    </div>

</div>
