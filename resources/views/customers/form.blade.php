<div class="row">

    <div class="col-md-6 mb-3">
        <label class="form-label">Nombre</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $customer->name ?? '') }}" required>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Documento</label>
        <input type="text" name="document" class="form-control"
            value="{{ old('document', $customer->document ?? '') }}" required>
    </div>

</div>
