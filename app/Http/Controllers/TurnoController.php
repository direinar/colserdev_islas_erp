<?php

namespace App\Http\Controllers;

use App\Concerns\ValidaPlanillaTurno;
use App\Models\Banco;
use App\Models\Customer;
use App\Models\Lubricant;
use App\Models\Turno;
use App\Services\BorradorPlanillaTurnoService;
use App\Services\GuardarPlanillaTurnoService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
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
    private const SECCIONES = ['ventas', 'lubricantes', 'consignaciones', 'descuentos', 'cartera', 'qrPagos', 'recaudos', 'transferencias', 'gasolinaEds', 'varios', 'recaudosAdmin'];

    public function create(Request $request, BorradorPlanillaTurnoService $borrador): View
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

        // Si el guardado falló (validación), se vuelve a mostrar lo que se había digitado
        // en lugar de la planilla vacía o de la última versión guardada.
        if ($request->session()->hasOldInput('numero_turno')) {
            $turno = $borrador->desdeEnvio((array) $request->old(), $turno, $request->user());
        }

        return view('planillas.turnos.create', compact('lubricants', 'customers', 'bancos', 'nextNumber', 'turno', 'turnosDeFecha', 'buscarFecha', 'searchFecha', 'searchNumero'));
    }

    public function store(Request $request, GuardarPlanillaTurnoService $planilla): RedirectResponse
    {
        $request->validate($this->reglasPlanilla());

        // El número de una planilla nueva se asigna al abrir "Nuevo": si otro usuario guardó ese
        // número mientras esta se digitaba, se guarda con el siguiente libre. Antes el administrador
        // sobrescribía la planilla del otro y a los demás roles se les rechazaba y perdían lo digitado.
        $numeroAbierto = (int) $request->input('numero_turno');
        $ocupadoPor = $request->boolean('planilla_nueva')
            ? Turno::query()->where('numero_turno', $numeroAbierto)->value('nombre_vendedor')
            : null;

        if ($ocupadoPor !== null) {
            $request->merge(['numero_turno' => (int) Turno::query()->max('numero_turno') + 1]);
        }

        $this->autorizarGuardado($request);
        $this->validarReglasDeNegocioPlanilla($request);

        $this->guardarSinChocar($request, $planilla);

        $numero = (int) $request->input('numero_turno');
        $mensaje = $numero !== $numeroAbierto
            ? 'Turno guardado correctamente como turno #'.$numero.': el #'.$numeroAbierto.' lo registró '
                .($ocupadoPor ?: 'otro usuario').' mientras usted digitaba esta planilla.'
            : 'Turno guardado correctamente.';

        return redirect()->route('turnos.create', [
            'fecha' => $request->input('fecha'),
            'numero_turno' => $numero,
        ])
            ->with('success', $mensaje)
            // Solo con un guardado exitoso se borra el borrador que guarda el navegador; si cambió
            // el número, también el borrador que quedó con el número con que se abrió.
            ->with('planilla_guardada', true)
            ->with('borrador_descartar', $numero !== $numeroAbierto ? $request->input('fecha').':'.$numeroAbierto : null);
    }

    /**
     * Dos planillas nuevas guardadas en el mismo instante pueden tomar el mismo número: la
     * segunda se guarda con el siguiente libre en vez de fallar.
     */
    private function guardarSinChocar(Request $request, GuardarPlanillaTurnoService $planilla): void
    {
        for ($intento = 1; ; $intento++) {
            try {
                $planilla->guardar($request->all(), $request->user());

                return;
            } catch (UniqueConstraintViolationException $error) {
                if (! $request->boolean('planilla_nueva') || $intento >= 3) {
                    throw $error;
                }

                $request->merge(['numero_turno' => (int) Turno::query()->max('numero_turno') + 1]);
            }
        }
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
     * Estado de la planilla para la pantalla: antes de guardar se consulta si otro usuario
     * la marcó como revisada mientras estaba abierta.
     */
    public function estado(Request $request, Turno $turno): JsonResponse
    {
        abort_unless($turno->visiblePara($request->user()), 403);

        return response()->json([
            'revisado' => (bool) $turno->revisado,
            'revisado_por' => $turno->revisado_por,
            'revisado_at' => $turno->revisado_at?->format('d/m/Y H:i'),
        ]);
    }

    /**
     * Devuelve a la planilla guardada con el motivo, sin reemplazarla por lo enviado.
     */
    private function rechazarGuardado(Request $request, Turno $turno, string $mensaje): never
    {
        $destino = $turno->visiblePara($request->user())
            ? route('turnos.create', ['turno_busqueda' => $turno->numero_turno])
            : route('turnos.create');

        throw new HttpResponseException(redirect($destino)->withErrors(['planilla' => $mensaje]));
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
        // administrador): primero debe volverla a PENDIENTE DE REVISIÓN. Pasa cuando otro
        // usuario la revisó mientras esta persona la tenía abierta: se vuelve a la planilla
        // con el aviso (en vez de una página 403) y lo digitado queda en el borrador del navegador.
        if ($existente->revisado) {
            $this->rechazarGuardado($request, $existente,
                'Sus cambios NO se guardaron: esta planilla la marcó como REVISADA '.$existente->revisado_por
                .($existente->revisado_at ? ' el '.$existente->revisado_at->format('d/m/Y').' a las '.$existente->revisado_at->format('H:i') : '')
                .'. Para corregirla, el administrador debe volverla a Pendiente de revisión; lo digitado se puede recuperar al abrirla de nuevo.');
        }

        if (! $existente->editablePor($request->user())) {
            $this->rechazarGuardado($request, $existente,
                'Sus cambios NO se guardaron: esta planilla la registró otro usuario; solo él o el administrador la pueden modificar.');
        }

        // Cambiar la fecha de una planilla ya guardada la movería de día en todos los informes.
        if (! $request->user()->isAdministrador()
            && $existente->fecha->format('Y-m-d') !== date('Y-m-d', strtotime((string) $request->input('fecha')))) {
            throw ValidationException::withMessages(['fecha' => 'Solo el administrador puede cambiar la fecha de una planilla ya guardada.']);
        }
    }
}
