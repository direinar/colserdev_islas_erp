{{--
    Bloque "Llevar a la nómina" de prima e intereses: elige la nómina en borrador y envía los valores.
    Parámetros: $concepto ('prima' | 'intereses_cesantias'), $valores (empleado_id => valor), $nominasBorrador, $nominaSugerida, $texto.
--}}
<form method="POST" action="{{ route('nomina.prestaciones.aplicar') }}" class="card border-primary mt-3 d-print-none" data-sin-aviso
    onsubmit="return confirm('¿Llevar los valores calculados a la nómina elegida? Reemplaza lo que tenga digitado en ese concepto.');">
    @csrf
    <input type="hidden" name="concepto" value="{{ $concepto }}">
    @foreach ($valores as $empleadoId => $valor)
        <input type="hidden" name="valores[{{ $empleadoId }}]" value="{{ $valor }}">
    @endforeach
    <div class="card-body d-flex flex-wrap gap-2 align-items-end">
        <div>
            <label for="liquidacion_id" class="form-label fw-semibold mb-1">{{ $texto }}</label>
            <select id="liquidacion_id" name="liquidacion_id" class="form-select" required>
                <option value="">Elija la nómina en borrador…</option>
                @foreach ($nominasBorrador as $nomina)
                    <option value="{{ $nomina->id }}" @selected($nominaSugerida === $nomina->id)>{{ $nomina->nombreDelMes() }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary" @disabled($nominasBorrador->isEmpty() || $valores === [])>
            <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> Llevar a la nómina
        </button>
        @if ($nominasBorrador->isEmpty())
            <span class="small text-muted">No hay nóminas en borrador. Cree la del mes en «Nómina del mes».</span>
        @endif
    </div>
</form>
