<?php

namespace App\Http\Controllers;

use App\Concerns\ValidaPlanillaTurno;
use App\Models\Banco;
use App\Models\Customer;
use App\Models\Lubricant;
use App\Models\Turno;
use App\Services\BorradorPlanillaTurnoService;
use App\Services\GuardarPlanillaTurnoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function create(Request $request, BorradorPlanillaTurnoService $borrador): View
    {
        $lubricants = Lubricant::query()->get()->sortBy('reference', SORT_NATURAL | SORT_FLAG_CASE)->values();
        $customers = Customer::orderBy('name', 'asc')->get();
        $bancos = Banco::orderBy('name', 'asc')->get();

        // Si vienen parámetros de búsqueda, cargar el turno
        $turno = null;
        $previousTurno = null;
        $searchFecha = $request->query('fecha');
        $searchNumero = $request->query('numero_turno');
        // Búsqueda independiente de la fecha: se ingresa solo el número de turno
        // y se localiza el registro más reciente que coincida con ese número.
        $searchTurnoSolo = $request->query('turno_busqueda');

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
                ->get(['id', 'numero_turno', 'fecha', 'nombre_vendedor', 'revisado'])
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

        if (! $turno && ! $searchNumero) {
            // Si es un nuevo turno, cargar el último turno registrado (consecutivo
            // global, sin importar la fecha) para precargar las lecturas iniciales
            // con sus finales.
            $previousTurno = Turno::with('surtidores')
                ->orderByDesc('numero_turno')
                ->first();
        }

        // Si el guardado falló (validación), se vuelve a mostrar lo que se había digitado
        // en lugar de la planilla vacía o de la última versión guardada.
        if ($request->session()->hasOldInput('numero_turno')) {
            $turno = $borrador->desdeEnvio((array) $request->old(), $turno, $request->user());
        }

        return view('planillas.turnos.create', compact('lubricants', 'customers', 'bancos', 'nextNumber', 'turno', 'previousTurno', 'turnosDeFecha', 'buscarFecha', 'searchFecha', 'searchNumero'));
    }

    public function store(Request $request, GuardarPlanillaTurnoService $planilla): RedirectResponse
    {
        $request->validate($this->reglasPlanilla());
        $this->autorizarGuardado($request);
        $this->validarReglasDeNegocioPlanilla($request);

        $planilla->guardar($request->all(), $request->user());

        return redirect()->route('turnos.create', [
            'fecha' => $request->input('fecha'),
            'numero_turno' => $request->input('numero_turno'),
        ])
            ->with('success', 'Turno guardado correctamente.');
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

        if (! $existente) {
            return;
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
