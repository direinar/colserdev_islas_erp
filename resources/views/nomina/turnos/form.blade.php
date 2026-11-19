@php($turno = $turno ?? null)

<div class="row">
    <div class="col-md-3 mb-3">
        <label for="campo-nombre" class="form-label">Nombre</label>
        <input id="campo-nombre" type="text" name="nombre" class="form-control" maxlength="30"
            value="{{ old('nombre', $turno?->nombre) }}" placeholder="Ej.: 6AM-2PM" required>
    </div>
    <div class="col-md-3 mb-3">
        <label for="campo-hora_inicio" class="form-label">Hora de inicio</label>
        <input id="campo-hora_inicio" type="time" name="hora_inicio" class="form-control"
            value="{{ old('hora_inicio', $turno ? substr($turno->hora_inicio, 0, 5) : '') }}" required>
    </div>
    <div class="col-md-3 mb-3">
        <label for="campo-hora_fin" class="form-label">Hora final</label>
        <input id="campo-hora_fin" type="time" name="hora_fin" class="form-control"
            value="{{ old('hora_fin', $turno ? substr($turno->hora_fin, 0, 5) : '') }}" required>
        <div class="form-text">Si es menor que la de inicio, el turno termina al día siguiente.</div>
    </div>
    <div class="col-md-2 mb-3">
        <label for="campo-color" class="form-label">Color en la planilla</label>
        <input id="campo-color" type="color" name="color" class="form-control form-control-color"
            value="{{ old('color', $turno?->color ?? '#cfe2ff') }}" required>
    </div>
    <div class="col-md-1 mb-3 d-flex align-items-center">
        <div class="form-check">
            <input id="campo-activo" type="checkbox" name="activo" value="1" class="form-check-input"
                @checked($errors->any() ? old('activo') : ($turno?->activo ?? true))>
            <label for="campo-activo" class="form-check-label">Activo</label>
        </div>
    </div>
</div>
