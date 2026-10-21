<?php

namespace App\Http\Controllers;

use App\Concerns\ValidaPlanillaTurno;
use App\Models\Banco;
use App\Models\Customer;
use App\Models\Lubricant;
use App\Models\Turno;
use App\Models\TurnoSurtidor;
use App\Services\BorradorPlanillaTurnoService;
use App\Services\GuardarPlanillaTurnoService;
use App\Services\ManguerasTurnoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Planilla de turnos: consultar, validar, autorizar y delegar. El guardado está en
 * GuardarPlanillaTurnoService y el borrador tras un error en BorradorPlanillaTurnoService.
 */
class TurnoController extends Controller
{
    use ValidaPlanillaTurno;

    /**
     * Relaciones que muestra la planilla.
     */
    private const SECCIONES = ['ventas', 'surtidores', 'lubricantes', 'consignaciones', 'descuentos', 'cartera', 'qrPagos', 'recaudos', 'transferencias', 'gasolinaEds', 'varios', 'recaudosAdmin'];

    public function create(Request $request, BorradorPlanillaTurnoService $borrador, ManguerasTurnoService $mangueras): View
    {
        $lubricants = Lubricant::query()->get()->sortBy('reference', SORT_NATURAL | SORT_FLAG_CASE)->values();
        $customers = Customer::orderBy('name', 'asc')->get();
        $bancos = Banco::orderBy('name', 'asc')->get();

        // Si vienen parámetros de búsqueda, cargar el turno
        $turno = null;
        $searchFecha = $request->query('fecha');
        $searchNumero = $request->query('numero_turno');
        // Búsqueda independiente de la fecha: se ingresa solo el número de turno
        // y se localiza el registro más reciente que coincida con ese número.
        // Tras un guardado rechazado se vuelve a la misma planilla con lo digitado.
        $searchTurnoSolo = $request->query('turno_busqueda') ?? $request->old('numero_turno');

        if (! $searchFecha && ! $searchNumero && $searchTurnoSolo) {
            $turno = Turno::with(self::SECCIONES)
                ->where('numero_turno', $searchTurnoSolo)
                ->orderByDesc('fecha')
                ->first();

            if ($turno) {
                $searchFecha = $turno->fecha->toDateString();
                $searchNumero = $turno->numero_turno;
            }
        }

        // Consecutivo global: numero_turno es único en toda la tabla (no se
        // reinicia por fecha), así que el próximo número es el máximo + 1 sin
        // importar la fecha consultada.
        $nextNumber = (int) Turno::max('numero_turno') + 1;

        // Buscador por fecha: lista las planillas registradas ese día para abrirlas.
        $buscarFecha = $request->query('buscar_fecha');
        $turnosDeFecha = $buscarFecha
            ? Turno::query()
                ->visiblePara($request->user())
                ->whereDate('fecha', $buscarFecha)
                ->orderBy('numero_turno')
                ->get(['id', 'numero_turno', 'fecha', 'nombre_vendedor', 'revisado', 'estado', 'turno_dia'])
            : collect();

        if (! $turno && $searchFecha && $searchNumero) {
            // 'fecha' se guarda con hora (cast date -> datetime ISO), por lo que se
            // compara solo la parte de fecha para evitar fallos de coincidencia exacta.
            $turno = Turno::with(self::SECCIONES)
                ->whereDate('fecha', $searchFecha)
                ->where('numero_turno', $searchNumero)
                ->first();
        }

        if ($turno && ! $turno->visiblePara($request->user())) {
            abort(403, 'Esta planilla la registró otro usuario y no tiene permiso para verla.');
        }

        // Si el guardado falló (validación), se vuelve a mostrar lo que se había digitado
        // en lugar de la planilla vacía o de la última versión guardada.
        if ($turno && $request->session()->hasOldInput('numero_turno')) {
            $turno = $borrador->desdeEnvio((array) $request->old(), $turno, $request->user());
        }

        // Sin planilla: se abre un turno nuevo tomando mangueras libres.
        $apertura = $turno ? null : [
            'ocupadas' => $mangueras->ocupadas(),
            'lecturas' => $mangueras->ultimasLecturas(),
            'hoy' => now()->toDateString(),
            'manana' => now()->addDay()->toDateString(),
            'turnoDiaHoy' => $mangueras->turnoDelDiaSugerido(now()->toDateString()),
            'turnoDiaManana' => $mangueras->turnoDelDiaSugerido(now()->addDay()->toDateString()),
        ];

        return view('planillas.turnos.create', compact('lubricants', 'customers', 'bancos', 'nextNumber', 'turno', 'apertura', 'turnosDeFecha', 'buscarFecha', 'searchFecha', 'searchNumero'));
    }

    /**
     * Abre la planilla: fecha (el turno que empieza en la noche es el turno 1 del día
     * siguiente), turno del día, hora de inicio y las mangueras que atenderá el islero.
     */
    public function abrir(Request $request, ManguerasTurnoService $mangueras): RedirectResponse
    {
        $esAdministrador = $request->user()->isAdministrador();

        $validated = $request->validate([
            // El islero y el jefe de patios abren para hoy o para mañana; el administrador, cualquier fecha.
            'fecha' => ['required', 'date', ...($esAdministrador ? [] : [Rule::in([now()->toDateString(), now()->addDay()->toDateString()])])],
            // En blanco: el siguiente turno del día de esa fecha.
            'turno_dia' => ['nullable', 'integer', 'min:1', 'max:9'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'mangueras' => ['required', 'array', 'min:1'],
            'mangueras.*' => ['distinct', Rule::in(array_keys(TurnoSurtidor::MANGUERAS))],
        ], [
            'fecha.in' => 'La fecha del turno debe ser hoy o mañana (el turno de la noche es el turno 1 del día siguiente).',
            'mangueras.required' => 'Seleccione las mangueras que atenderá en el turno.',
        ]);

        $validated['turno_dia'] ??= $mangueras->turnoDelDiaSugerido($validated['fecha']);

        if (Turno::query()->whereDate('fecha', $validated['fecha'])->where('turno_dia', $validated['turno_dia'])->exists()) {
            throw ValidationException::withMessages(['turno_dia' => 'Ya existe el turno '.$validated['turno_dia'].' del '.$validated['fecha'].'.']);
        }

        $turno = $mangueras->abrir($validated, $request->user());

        return redirect()->route('turnos.create', ['turno_busqueda' => $turno->numero_turno])
            ->with('success', 'Turno #'.$turno->numero_turno.' abierto con '.count($validated['mangueras']).' manguera(s). Al terminar, digite las lecturas finales y use CERRAR TURNO.');
    }

    public function store(Request $request, GuardarPlanillaTurnoService $planilla, ManguerasTurnoService $mangueras): RedirectResponse
    {
        $request->validate($this->reglasPlanilla());
        $this->autorizarGuardado($request);
        $this->validarReglasDeNegocioPlanilla($request);

        $turno = $planilla->guardar($request->all(), $request->user());

        $destino = redirect()->route('turnos.create', [
            'fecha' => $request->input('fecha'),
            'numero_turno' => $request->input('numero_turno'),
        ]);

        if (! $request->boolean('cerrar') || ! $turno->estaAbierta()) {
            return $destino->with('success', 'Turno guardado correctamente.');
        }

        try {
            $mangueras->cerrar($turno, $request->user());
        } catch (ValidationException $error) {
            return $destino->with('success', 'Turno guardado, pero NO se cerró.')->withErrors($error->errors());
        }

        return $destino->with('success', 'Turno #'.$turno->numero_turno.' guardado y CERRADO. Sus mangueras quedaron libres para el siguiente turno.');
    }

    /**
     * El jefe de patios (o el administrador) cierra la planilla de un islero que no la cerró:
     * solo digita las lecturas finales.
     */
    public function cerrar(Request $request, Turno $turno, GuardarPlanillaTurnoService $planilla, ManguerasTurnoService $mangueras): RedirectResponse
    {
        abort_unless($turno->cerrablePor($request->user()), 403, 'No puede cerrar esta planilla.');

        $request->validate([
            'finales' => ['required', 'array'],
            'finales.*' => ['nullable', 'string', 'max:30'],
        ]);

        $planilla->registrarLecturasFinales($turno, (array) $request->input('finales'));
        $mangueras->cerrar($turno, $request->user());

        return redirect()->route('turnos.create', ['turno_busqueda' => $turno->numero_turno])
            ->with('success', 'Turno #'.$turno->numero_turno.' CERRADO. Sus mangueras quedaron libres para el siguiente turno.');
    }

    public function pendientes(Request $request): View
    {
        $turnos = Turno::query()
            ->where('revisado', false)
            ->orderByDesc('fecha')
            ->orderByDesc('numero_turno')
            ->paginate(20);

        return view('planillas.turnos.pendientes', compact('turnos'));
    }

    public function revisar(Request $request, Turno $turno): RedirectResponse
    {
        if ($turno->estaAbierta()) {
            return back()->withErrors(['revisar' => 'Turno #'.$turno->numero_turno.': la planilla sigue ABIERTA. Debe cerrarse con las lecturas finales antes de revisarla.']);
        }

        // No se revisa una planilla con SOBRANTE o FALTANTE sin trasladar.
        if ($turno->sobrante_pendiente === null) {
            return back()->withErrors(['revisar' => 'Turno #'.$turno->numero_turno.': abra la planilla y guárdela antes de marcarla como revisada, para verificar el sobrante o faltante.']);
        }

        if (abs((float) $turno->sobrante_pendiente) >= 1) {
            $tipo = (float) $turno->sobrante_pendiente < 0 ? 'FALTANTE' : 'SOBRANTE';

            return back()->withErrors(['revisar' => 'Turno #'.$turno->numero_turno.': tiene un '.$tipo.' de $'.number_format(abs((float) $turno->sobrante_pendiente), 0, ',', '.').' sin trasladar. Use TRASLADAR y guarde la planilla antes de marcarla como revisada.']);
        }

        $turno->update([
            'revisado' => true,
            'revisado_por' => $request->user()->name,
            'revisado_at' => now(),
        ]);

        return back()->with('success', 'Turno #'.$turno->numero_turno.' del '.$turno->fecha->format('Y-m-d').' marcado como revisado.');
    }

    /**
     * El administrador devuelve una planilla revisada a PENDIENTE DE REVISIÓN
     * para hacerle correcciones o adiciones.
     */
    public function reabrir(Request $request, Turno $turno): RedirectResponse
    {
        $turno->update([
            'revisado' => false,
            'revisado_por' => null,
            'revisado_at' => null,
        ]);

        return redirect()->route('turnos.create', ['turno_busqueda' => $turno->numero_turno])
            ->with('success', 'Turno #'.$turno->numero_turno.' devuelto a PENDIENTE DE REVISIÓN. Ya se puede corregir.');
    }

    /**
     * Quién puede guardar sobre una planilla que ya existe.
     */
    private function autorizarGuardado(Request $request): void
    {
        // numero_turno es un consecutivo único en toda la tabla, así que basta
        // con buscarlo por ese campo para saber si la planilla ya existe.
        $existente = Turno::query()->where('numero_turno', $request->input('numero_turno'))->first();

        // Las planillas se crean con ABRIR TURNO (mangueras y lecturas iniciales), no al guardar.
        if (! $existente) {
            throw ValidationException::withMessages(['numero_turno' => 'El turno #'.$request->input('numero_turno').' no existe. Primero use ABRIR TURNO con sus mangueras.']);
        }

        // Una planilla revisada no se modifica ni se alimenta (ni siquiera el
        // administrador): primero debe volverla a PENDIENTE DE REVISIÓN.
        if ($existente->revisado) {
            abort(403, 'Esta planilla está REVISADA y no se puede modificar. El administrador debe volverla a Pendiente de revisión.');
        }

        if (! $existente->editablePor($request->user())) {
            abort(403, 'Esta planilla la registró otro usuario; solo él o el administrador la pueden modificar.');
        }

        // Cambiar la fecha de una planilla ya guardada la movería de día en todos los informes.
        if (! $request->user()->isAdministrador()
            && $existente->fecha->format('Y-m-d') !== date('Y-m-d', strtotime((string) $request->input('fecha')))) {
            throw ValidationException::withMessages(['fecha' => 'Solo el administrador puede cambiar la fecha de una planilla ya guardada.']);
        }
    }
}
