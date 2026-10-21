{{-- ABRIR TURNO: fecha, turno del día, hora de inicio y mangueras que atenderá el islero.
     Una manguera solo puede estar en una planilla abierta; la lectura inicial es la última
     lectura final cerrada de cada manguera. --}}
@php
    use App\Models\TurnoSurtidor;
    use Illuminate\Support\Carbon;

    $esAdministrador = auth()->user()->isAdministrador();
    // El turno de la noche es el turno 1 del día siguiente: desde las 6 p. m. se propone mañana.
    $fechaPropuesta = old('fecha', now()->hour >= 18 ? $apertura['manana'] : $apertura['hoy']);
    $islas = array_chunk(TurnoSurtidor::MANGUERAS, 4, true);
    $abiertos = $apertura['ocupadas']->groupBy(fn ($fila) => $fila->turno_id);
@endphp

<style>
    .apertura-manguera {
        border: 1px solid #ced4da;
        border-radius: .5rem;
        padding: .5rem .75rem;
        height: 100%;
    }

    .apertura-manguera.ocupada {
        background: #f8d7da;
        border-color: #f1aeb5;
    }

    .apertura-manguera .form-check-input {
        transform: scale(1.3);
        margin-right: .4rem;
    }
</style>

@if ($abiertos->isNotEmpty())
    <div class="pastel-section mb-3">
        <h2 class="h6 fw-bold mb-2">TURNOS ABIERTOS</h2>
        <div class="d-flex flex-wrap gap-2">
            @foreach ($abiertos as $filas)
                @php($abierto = $filas->first()->turno)
                <a href="{{ route('turnos.create', ['turno_busqueda' => $abierto->numero_turno]) }}"
                    class="btn btn-sm btn-outline-warning text-dark">
                    Turno {{ str_pad($abierto->numero_turno, 3, '0', STR_PAD_LEFT) }}
                    · {{ $abierto->user?->name ?? $abierto->nombre_vendedor }}
                    · {{ $filas->pluck('manguera')->implode(', ') }}
                </a>
            @endforeach
        </div>
    </div>
@endif

<form method="POST" action="{{ route('turnos.abrir') }}" class="pastel-section mb-3" id="abrir-turno-form">
    @csrf
    <h2 class="h5 fw-bold mb-3">ABRIR TURNO</h2>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-sm-4">
            <label class="form-label fw-semibold" for="apertura-fecha">FECHA DE LA PLANILLA</label>
            @if ($esAdministrador)
                <input type="date" name="fecha" id="apertura-fecha" class="form-control" value="{{ $fechaPropuesta }}" required>
            @else
                <select name="fecha" id="apertura-fecha" class="form-select" required>
                    <option value="{{ $apertura['hoy'] }}" @selected($fechaPropuesta === $apertura['hoy'])>
                        HOY — {{ Carbon::parse($apertura['hoy'])->format('d/m/Y') }}
                    </option>
                    <option value="{{ $apertura['manana'] }}" @selected($fechaPropuesta === $apertura['manana'])>
                        MAÑANA — {{ Carbon::parse($apertura['manana'])->format('d/m/Y') }} (turno de la noche)
                    </option>
                </select>
            @endif
            <div class="form-text">El turno que empieza en la noche es el turno 1 del día siguiente.</div>
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-semibold" for="apertura-turno-dia">TURNO DEL DÍA</label>
            {{-- El administrador puede abrir cualquier fecha: en blanco toma el siguiente turno de esa fecha. --}}
            <input type="number" name="turno_dia" id="apertura-turno-dia" class="form-control" min="1" max="9"
                @if ($esAdministrador) placeholder="Automático" @else required @endif
                value="{{ old('turno_dia', $esAdministrador ? '' : ($fechaPropuesta === $apertura['manana'] ? $apertura['turnoDiaManana'] : $apertura['turnoDiaHoy'])) }}">
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-semibold" for="apertura-hora">HORA DE INICIO</label>
            <input type="time" name="hora_inicio" id="apertura-hora" class="form-control" required
                value="{{ old('hora_inicio', now()->format('H:i')) }}">
        </div>
    </div>

    <div class="fw-semibold mb-2">MANGUERAS QUE ATENDERÁ (lectura inicial = última lectura cerrada)</div>
    @foreach ($islas as $numeroIsla => $mangueras)
        <div class="row g-2 mb-2">
            <div class="col-12 small text-muted">ISLA {{ $numeroIsla + 1 }}</div>
            @foreach ($mangueras as $manguera => $combustible)
                @php($ocupada = $apertura['ocupadas']->get($manguera))
                <div class="col-6 col-md-3">
                    <label class="apertura-manguera d-block {{ $ocupada ? 'ocupada' : '' }}">
                        <input type="checkbox" class="form-check-input" name="mangueras[]" value="{{ $manguera }}"
                            @checked(in_array($manguera, (array) old('mangueras', []), true)) @disabled($ocupada)>
                        <strong>{{ $manguera }}</strong>
                        <div class="small">
                            @if ($ocupada)
                                Ocupada: turno #{{ $ocupada->turno->numero_turno }} de
                                {{ $ocupada->turno->user?->name ?? $ocupada->turno->nombre_vendedor }}
                            @else
                                Inicial: {{ isset($apertura['lecturas'][$manguera]) ? number_format($apertura['lecturas'][$manguera], 3, '.', ',') : 'sin lectura anterior' }}
                            @endif
                        </div>
                    </label>
                </div>
            @endforeach
        </div>
    @endforeach

    <div class="text-end mt-3">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-unlock" aria-hidden="true"></i> ABRIR TURNO
        </button>
    </div>
</form>

@unless ($esAdministrador)
    <script>
        // Al cambiar entre hoy y mañana se propone el siguiente turno del día de esa fecha.
        document.getElementById('apertura-fecha')?.addEventListener('change', function() {
            const sugeridos = @json([$apertura['hoy'] => $apertura['turnoDiaHoy'], $apertura['manana'] => $apertura['turnoDiaManana']]);
            document.getElementById('apertura-turno-dia').value = sugeridos[this.value] ?? 1;
        });
    </script>
@endunless
