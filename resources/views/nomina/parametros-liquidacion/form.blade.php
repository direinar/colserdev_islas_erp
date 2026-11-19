@php($parametro = $parametro ?? null)
@php($hora = fn (?string $valor): string => $valor ? substr($valor, 0, 5) : '')

<div class="card mb-3">
    <div class="card-header fw-semibold">Vigencia</div>
    <div class="card-body row">
        <div class="col-md-4 mb-3">
            <label for="campo-vigente_desde" class="form-label">Vigente desde</label>
            <input id="campo-vigente_desde" type="date" name="vigente_desde" class="form-control"
                value="{{ old('vigente_desde', $parametro?->vigente_desde?->toDateString()) }}" required>
            <div class="form-text">Rige hasta el día anterior a la siguiente vigencia.</div>
        </div>
        <div class="col-md-8 mb-3">
            <label for="campo-descripcion" class="form-label">Descripción</label>
            <input id="campo-descripcion" type="text" name="descripcion" class="form-control"
                value="{{ old('descripcion', $parametro?->descripcion) }}" placeholder="Ej.: Salario mínimo 2026">
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header fw-semibold">Devengados</div>
    <div class="card-body row">
        <div class="col-md-3 mb-3">
            <label for="campo-salario_minimo" class="form-label">Salario básico (mes)</label>
            <input id="campo-salario_minimo" type="text" inputmode="numeric" name="salario_minimo" class="form-control text-end"
                value="{{ old('salario_minimo', $parametro ? number_format((float) $parametro->salario_minimo, 0, ',', '.') : '') }}" required>
        </div>
        <div class="col-md-3 mb-3">
            <label for="campo-auxilio_transporte" class="form-label">Auxilio de transporte (mes)</label>
            <input id="campo-auxilio_transporte" type="text" inputmode="numeric" name="auxilio_transporte" class="form-control text-end"
                value="{{ old('auxilio_transporte', $parametro ? number_format((float) $parametro->auxilio_transporte, 0, ',', '.') : '') }}" required>
        </div>
        <div class="col-md-3 mb-3">
            <label for="campo-horas_mensuales" class="form-label">Horas mensuales (divisor)</label>
            <input id="campo-horas_mensuales" type="number" min="1" step="1" name="horas_mensuales" class="form-control text-end"
                value="{{ old('horas_mensuales', $parametro?->horas_mensuales ?? 220) }}" required>
            <div class="form-text">Valor hora = salario básico ÷ este número.</div>
        </div>
        <div class="col-md-3 mb-3">
            <label for="campo-horas_semanales" class="form-label">Jornada semanal (horas)</label>
            <input id="campo-horas_semanales" type="number" min="1" step="1" name="horas_semanales" class="form-control text-end"
                value="{{ old('horas_semanales', $parametro?->horas_semanales ?? 42) }}" required>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header fw-semibold">Franjas de recargo</div>
    <div class="card-body row">
        <div class="col-md-3 mb-3">
            <label for="campo-hora_inicio_nocturna" class="form-label">Recargo nocturno desde</label>
            <input id="campo-hora_inicio_nocturna" type="time" name="hora_inicio_nocturna" class="form-control"
                value="{{ old('hora_inicio_nocturna', $hora($parametro?->hora_inicio_nocturna) ?: '19:00') }}" required>
        </div>
        <div class="col-md-3 mb-3">
            <label for="campo-hora_fin_nocturna" class="form-label">Recargo nocturno hasta</label>
            <input id="campo-hora_fin_nocturna" type="time" name="hora_fin_nocturna" class="form-control"
                value="{{ old('hora_fin_nocturna', $hora($parametro?->hora_fin_nocturna) ?: '06:00') }}" required>
        </div>
        <div class="col-md-3 mb-3">
            <label for="campo-hora_inicio_festiva" class="form-label">Recargo dominical/festivo desde</label>
            <input id="campo-hora_inicio_festiva" type="time" name="hora_inicio_festiva" class="form-control"
                value="{{ old('hora_inicio_festiva', $hora($parametro?->hora_inicio_festiva) ?: '06:00') }}" required>
        </div>
        <div class="col-md-3 mb-3">
            <label for="campo-hora_fin_festiva" class="form-label">Recargo dominical/festivo hasta</label>
            <input id="campo-hora_fin_festiva" type="time" name="hora_fin_festiva" class="form-control"
                value="{{ old('hora_fin_festiva', $hora($parametro?->hora_fin_festiva) ?: '00:00') }}" required>
            <div class="form-text">00:00 = hasta la medianoche. Con 00:00 a 00:00 cuenta todo el día.</div>
        </div>
    </div>
</div>

@foreach (App\Models\Nomina\ParametroLiquidacion::gruposPorcentajes() as $grupo => $campos)
    <div class="card mb-3">
        <div class="card-header fw-semibold">{{ $grupo }}</div>
        <div class="card-body row">
            @foreach ($campos as $campo => $etiqueta)
                <div class="col-md-3 mb-3">
                    <label for="campo-{{ $campo }}" class="form-label">{{ $etiqueta }}</label>
                    <div class="input-group">
                        <input id="campo-{{ $campo }}" type="number" min="0" max="999" step="0.001" name="{{ $campo }}"
                            class="form-control text-end" value="{{ old($campo, $parametro ? (float) $parametro->{$campo} : '') }}" required>
                        <span class="input-group-text">%</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endforeach
