<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\RegistrarEventoAplicacion;
use App\Services\RegistroEventosAplicacionService;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);

        // Auditoría del administrador: acciones de los usuarios (guardar, modificar, eliminar).
        $middleware->web(append: [
            RegistrarEventoAplicacion::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Auditoría del administrador: cada falla del sistema queda para gestionarla.
        // Laravel sigue escribiendo la falla en el log.
        $exceptions->report(function (Throwable $excepcion): void {
            app(RegistroEventosAplicacionService::class)->registrarError($excepcion);
        });
    })->create();
