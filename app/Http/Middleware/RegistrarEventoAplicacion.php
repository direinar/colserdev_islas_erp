<?php

namespace App\Http\Middleware;

use App\Services\RegistroEventosAplicacionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Registra en la Auditoría del administrador cada acción de un usuario que guarda, modifica
 * o elimina información en las pantallas del ERP. Las consultas (GET) no se registran, ni
 * las fallas del servidor (5xx): esas las registra el manejador de excepciones.
 */
class RegistrarEventoAplicacion
{
    /**
     * Rutas que no se registran: la propia gestión de fallas y eventos.
     */
    private const EXCLUIDAS = ['control-planillas.eventos.'];

    public function __construct(private RegistroEventosAplicacionService $registro) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->debeRegistrar($request, $response)) {
            $this->registro->registrarAccion($request, $response);
        }

        return $response;
    }

    private function debeRegistrar(Request $request, Response $response): bool
    {
        if ($request->isMethodSafe() || $request->user() === null || $response->getStatusCode() >= 500) {
            return false;
        }

        $ruta = $request->route();
        $nombre = (string) $ruta?->getName();

        // Solo las pantallas propias del ERP (no Livewire, Fortify ni paquetes).
        if ($nombre === '' || ! str_starts_with((string) $ruta->getControllerClass(), 'App\\')) {
            return false;
        }

        foreach (self::EXCLUIDAS as $prefijo) {
            if (str_starts_with($nombre, $prefijo)) {
                return false;
            }
        }

        return true;
    }
}
