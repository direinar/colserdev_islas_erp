<div class="row">

    <div class="col-md-6 mb-3">
        <label for="campo-name" class="form-label">Nombre</label>
        <input id="campo-name" type="text" name="name" class="form-control" value="{{ old('name', $proveedor->name ?? '') }}"
            required>
    </div>

    <div class="col-md-6 mb-3">
        <label for="campo-document" class="form-label">Documento</label>
        <input id="campo-document" type="text" name="document" class="form-control"
            value="{{ old('document', $proveedor->document ?? '') }}" required>
    </div>

</div>
