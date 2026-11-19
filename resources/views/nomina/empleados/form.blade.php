@php($empleado = $empleado ?? null)

<div class="card mb-3">
    <div class="card-header fw-semibold">Datos personales</div>
    <div class="card-body row">
        <div class="col-md-3 mb-3">
            <label for="campo-tipo_documento" class="form-label">Tipo de documento</label>
            <select id="campo-tipo_documento" name="tipo_documento" class="form-select" required>
                @foreach (App\Models\Nomina\Empleado::tiposDocumento() as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected(old('tipo_documento', $empleado?->tipo_documento ?? 'CC') === $valor)>{{ $etiqueta }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 mb-3">
            <label for="campo-numero_documento" class="form-label">Número de documento</label>
            <input id="campo-numero_documento" type="text" name="numero_documento" class="form-control"
                value="{{ old('numero_documento', $empleado?->numero_documento) }}" required>
        </div>
        <div class="col-md-3 mb-3">
            <label for="campo-nombres" class="form-label">Nombres</label>
            <input id="campo-nombres" type="text" name="nombres" class="form-control" value="{{ old('nombres', $empleado?->nombres) }}" required>
        </div>
        <div class="col-md-3 mb-3">
            <label for="campo-apellidos" class="form-label">Apellidos</label>
            <input id="campo-apellidos" type="text" name="apellidos" class="form-control" value="{{ old('apellidos', $empleado?->apellidos) }}" required>
        </div>
        <div class="col-md-3 mb-3">
            <label for="campo-nombre_corto" class="form-label">Nombre corto</label>
            <input id="campo-nombre_corto" type="text" name="nombre_corto" class="form-control" maxlength="30"
                value="{{ old('nombre_corto', $empleado?->nombre_corto) }}" required>
            <div class="form-text">Como aparece en la planilla de turnos (p. ej. ROSA).</div>
        </div>
        <div class="col-md-3 mb-3">
            <label for="campo-telefono" class="form-label">Teléfono</label>
            <input id="campo-telefono" type="text" name="telefono" class="form-control" value="{{ old('telefono', $empleado?->telefono) }}">
        </div>
        <div class="col-md-3 mb-3">
            <label for="campo-correo" class="form-label">Correo</label>
            <input id="campo-correo" type="email" name="correo" class="form-control" value="{{ old('correo', $empleado?->correo) }}">
        </div>
        <div class="col-md-3 mb-3">
            <label for="campo-direccion" class="form-label">Dirección</label>
            <input id="campo-direccion" type="text" name="direccion" class="form-control" value="{{ old('direccion', $empleado?->direccion) }}">
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header fw-semibold">Contrato y salario</div>
    <div class="card-body row">
        <div class="col-md-4 mb-3">
            <label for="campo-cargo" class="form-label">Cargo</label>
            <input id="campo-cargo" type="text" name="cargo" class="form-control" value="{{ old('cargo', $empleado?->cargo) }}" required>
        </div>
        <div class="col-md-4 mb-3">
            <label for="campo-fecha_ingreso" class="form-label">Fecha de ingreso</label>
            <input id="campo-fecha_ingreso" type="date" name="fecha_ingreso" class="form-control"
                value="{{ old('fecha_ingreso', $empleado?->fecha_ingreso?->toDateString()) }}" required>
        </div>
        <div class="col-md-4 mb-3">
            <label for="campo-salario_basico" class="form-label">Sueldo básico mensual</label>
            <input id="campo-salario_basico" type="text" inputmode="numeric" name="salario_basico" class="form-control text-end"
                value="{{ old('salario_basico', $empleado ? number_format((float) $empleado->salario_basico, 0, ',', '.') : '') }}" required>
            @if ($parametros)
                <div class="form-text">Salario mínimo vigente: $ {{ number_format((float) $parametros->salario_minimo, 0, ',', '.') }}</div>
            @endif
        </div>
        <div class="col-md-4 mb-3">
            <label for="campo-tipo_contrato" class="form-label">Tipo de contrato</label>
            <select id="campo-tipo_contrato" name="tipo_contrato" class="form-select" required>
                @foreach (App\Models\Nomina\Empleado::tiposContrato() as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected(old('tipo_contrato', $empleado?->tipo_contrato ?? 'indefinido') === $valor)>{{ $etiqueta }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label for="campo-fecha_vencimiento_contrato" class="form-label">Vencimiento del contrato</label>
            <input id="campo-fecha_vencimiento_contrato" type="date" name="fecha_vencimiento_contrato" class="form-control"
                value="{{ old('fecha_vencimiento_contrato', $empleado?->fecha_vencimiento_contrato?->toDateString()) }}">
            <div class="form-text">Obligatorio para término fijo y aprendizaje.</div>
        </div>
        <div class="col-md-4 mb-3 d-flex flex-column justify-content-center">
            <div class="form-check">
                <input id="campo-recibe_auxilio_transporte" type="checkbox" name="recibe_auxilio_transporte" value="1" class="form-check-input"
                    @checked($errors->any() ? old('recibe_auxilio_transporte') : $empleado?->recibe_auxilio_transporte)>
                <label for="campo-recibe_auxilio_transporte" class="form-check-label">Recibe auxilio de transporte</label>
                @if ($parametros)
                    <div class="form-text">Por ley, para quienes ganan hasta 2 salarios mínimos ($ {{ number_format((float) $parametros->salario_minimo * 2, 0, ',', '.') }}).</div>
                @endif
            </div>
            <div class="form-check mt-2">
                <input id="campo-aplica_recargos" type="checkbox" name="aplica_recargos" value="1" class="form-check-input"
                    @checked($errors->any() ? old('aplica_recargos') : $empleado?->aplica_recargos)>
                <label for="campo-aplica_recargos" class="form-check-label">Sobresueldos y recargos</label>
                <div class="form-text">Aparece en la planilla de turnos y se le liquidan recargos nocturnos y festivos.</div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header fw-semibold">Seguridad social</div>
    <div class="card-body row">
        <div class="col-md-4 mb-3">
            <label for="campo-eps" class="form-label">EPS</label>
            <input id="campo-eps" type="text" name="eps" class="form-control" value="{{ old('eps', $empleado?->eps) }}">
        </div>
        <div class="col-md-4 mb-3">
            <label for="campo-fondo_pension" class="form-label">Fondo de pensiones</label>
            <input id="campo-fondo_pension" type="text" name="fondo_pension" class="form-control" value="{{ old('fondo_pension', $empleado?->fondo_pension) }}">
        </div>
        <div class="col-md-4 mb-3">
            <label for="campo-fondo_cesantias" class="form-label">Fondo de cesantías</label>
            <input id="campo-fondo_cesantias" type="text" name="fondo_cesantias" class="form-control" value="{{ old('fondo_cesantias', $empleado?->fondo_cesantias) }}">
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header fw-semibold">Forma de pago de la nómina</div>
    <div class="card-body row">
        <div class="col-md-3 mb-3">
            <label for="campo-forma_pago" class="form-label">Forma de pago</label>
            <select id="campo-forma_pago" name="forma_pago" class="form-select" required>
                @foreach (App\Models\Nomina\Empleado::formasPago() as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected(old('forma_pago', $empleado?->forma_pago ?? 'transferencia') === $valor)>{{ $etiqueta }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 mb-3">
            <label for="campo-banco" class="form-label">Banco</label>
            <input id="campo-banco" type="text" name="banco" class="form-control" value="{{ old('banco', $empleado?->banco) }}">
        </div>
        <div class="col-md-3 mb-3">
            <label for="campo-tipo_cuenta" class="form-label">Tipo de cuenta</label>
            <select id="campo-tipo_cuenta" name="tipo_cuenta" class="form-select">
                <option value="">—</option>
                @foreach (App\Models\Nomina\Empleado::tiposCuenta() as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected(old('tipo_cuenta', $empleado?->tipo_cuenta) === $valor)>{{ $etiqueta }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 mb-3">
            <label for="campo-numero_cuenta" class="form-label">Número de cuenta</label>
            <input id="campo-numero_cuenta" type="text" name="numero_cuenta" class="form-control" value="{{ old('numero_cuenta', $empleado?->numero_cuenta) }}">
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header fw-semibold">Estado</div>
    <div class="card-body row">
        <div class="col-md-4 mb-3 d-flex align-items-center">
            <div class="form-check">
                <input id="campo-activo" type="checkbox" name="activo" value="1" class="form-check-input"
                    @checked($errors->any() ? old('activo') : ($empleado?->activo ?? true))>
                <label for="campo-activo" class="form-check-label">Empleado activo</label>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <label for="campo-fecha_retiro" class="form-label">Fecha de retiro</label>
            <input id="campo-fecha_retiro" type="date" name="fecha_retiro" class="form-control"
                value="{{ old('fecha_retiro', $empleado?->fecha_retiro?->toDateString()) }}">
        </div>
        <div class="col-md-4 mb-3">
            <label for="campo-motivo_retiro" class="form-label">Motivo del retiro</label>
            <input id="campo-motivo_retiro" type="text" name="motivo_retiro" class="form-control" value="{{ old('motivo_retiro', $empleado?->motivo_retiro) }}">
        </div>
    </div>
</div>
