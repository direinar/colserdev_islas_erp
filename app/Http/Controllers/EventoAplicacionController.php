<?php

namespace App\Http\Controllers;

use App\Concerns\ResuelveRangoFechas;
use App\Models\EventoAplicacion;
use App\Models\User;
use App\Services\RegistroEventosAplicacionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Auditoría del administrador → Fallas y eventos de la aplicación: semáforo, filtros y
 * gestión (pendiente, en revisión, resuelto, descartado) con observación.
 */
class EventoAplicacionController extends Controller
{
    use ResuelveRangoFechas;

    public function index(Request $request, RegistroEventosAplicacionService $registro): View
    {
        [$desde, $hasta] = $this->rango($request);

        $filtros = $request->validate([
            'vista' => ['nullable', Rule::in(['por_gestionar', 'rojo', 'amarillo', 'verde', 'gris', 'todos'])],
            'tipo' => ['nullable', Rule::in(array_keys(EventoAplicacion::TIPOS))],
            'modulo' => ['nullable', 'string', 'max:40'],
            'usuario' => ['nullable', 'integer'],
            'buscar' => ['nullable', 'string', 'max:100'],
        ]);
        $vista = $filtros['vista'] ?? 'por_gestionar';

        $eventos = EventoAplicacion::query()
            ->with(['user', 'gestor'])
            // Lo que espera gestión se ve aunque sea de antes del periodo.
            ->when($vista === 'por_gestionar', fn (Builder $query) => $query->porGestionar())
            ->when(in_array($vista, ['rojo', 'amarillo'], true), fn (Builder $query) => $query->semaforo($vista))
            ->when(in_array($vista, ['verde', 'gris', 'todos'], true), function (Builder $query) use ($vista, $desde, $hasta): void {
                $query->whereBetween('ultima_vez', [$desde.' 00:00:00', $hasta.' 23:59:59'])
                    ->when($vista !== 'todos', fn (Builder $query) => $query->semaforo($vista));
            })
            ->when($filtros['tipo'] ?? null, fn (Builder $query, string $tipo) => $query->where('tipo', $tipo))
            ->when($filtros['modulo'] ?? null, fn (Builder $query, string $modulo) => $query->where('modulo', $modulo))
            ->when($filtros['usuario'] ?? null, fn (Builder $query, int|string $usuario) => $query->where('user_id', $usuario))
            ->when($filtros['buscar'] ?? null, function (Builder $query, string $texto): void {
                $query->where(fn (Builder $query) => $query->where('titulo', 'like', '%'.$texto.'%')
                    ->orWhere('descripcion', 'like', '%'.$texto.'%')
                    ->orWhere('url', 'like', '%'.$texto.'%'));
            })
            // Primero lo crítico, luego lo más reciente.
            ->orderByRaw('case nivel when ? then 0 when ? then 1 else 2 end', [EventoAplicacion::NIVEL_CRITICO, EventoAplicacion::NIVEL_ADVERTENCIA])
            ->latest('ultima_vez')
            ->paginate(40)
            ->withQueryString();

        return view('control-planillas.eventos', [
            'desde' => $desde,
            'hasta' => $hasta,
            'vista' => $vista,
            'filtros' => $filtros,
            'eventos' => $eventos,
            'semaforo' => $registro->semaforo($desde, $hasta),
            'porGestionar' => EventoAplicacion::query()->porGestionar()->count(),
            'modulos' => EventoAplicacion::query()->whereNotNull('modulo')->distinct()->orderBy('modulo')->pluck('modulo'),
            'usuarios' => User::query()->whereIn('id', EventoAplicacion::query()->whereNotNull('user_id')->select('user_id'))->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function gestionar(Request $request, EventoAplicacion $evento, RegistroEventosAplicacionService $registro): RedirectResponse
    {
        $datos = $request->validate([
            'estado' => ['required', Rule::in(array_keys(EventoAplicacion::ESTADOS_GESTION))],
            'observacion' => [
                Rule::requiredIf(in_array($request->input('estado'), [EventoAplicacion::ESTADO_RESUELTO, EventoAplicacion::ESTADO_DESCARTADO], true)),
                'nullable', 'string', 'max:1000',
            ],
        ], [
            'observacion.required' => 'Escriba qué se hizo (o por qué se descarta) para cerrar el evento.',
        ]);

        $registro->gestionar($evento, $datos['estado'], $datos['observacion'] ?? null, $request->user());

        return back()->with('success', 'Evento #'.$evento->id.' quedó '.mb_strtoupper(EventoAplicacion::ESTADOS_GESTION[$datos['estado']]).'.');
    }

    /**
     * Gestión en lote: los eventos marcados pasan al mismo estado con la misma observación.
     */
    public function gestionarVarios(Request $request, RegistroEventosAplicacionService $registro): RedirectResponse
    {
        $datos = $request->validate([
            'eventos' => ['required', 'array', 'max:200'],
            'eventos.*' => ['integer'],
            'estado' => ['required', Rule::in([EventoAplicacion::ESTADO_EN_REVISION, EventoAplicacion::ESTADO_RESUELTO, EventoAplicacion::ESTADO_DESCARTADO])],
            'observacion' => ['required', 'string', 'max:1000'],
        ], [
            'eventos.required' => 'Marque al menos un evento.',
            'observacion.required' => 'Escriba la observación de la gestión.',
        ]);

        $eventos = EventoAplicacion::query()->whereIn('id', $datos['eventos'])->porGestionar()->get();
        foreach ($eventos as $evento) {
            $registro->gestionar($evento, $datos['estado'], $datos['observacion'], $request->user());
        }

        return back()->with('success', $eventos->count().' evento(s) quedaron '.mb_strtoupper(EventoAplicacion::ESTADOS_GESTION[$datos['estado']]).'.');
    }
}
