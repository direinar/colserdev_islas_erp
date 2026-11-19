<?php

namespace App\Providers;

use App\Models\User;
use App\Services\KardexInventarioService;
use App\Services\RegistroEventosAplicacionService;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Una instancia por petición: los informes comparten los kardex ya calculados.
        $this->app->scoped(KardexInventarioService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registrarEventosDeAcceso();
    }

    /**
     * Ingresos e intentos fallidos de ingreso para la Auditoría del administrador.
     */
    protected function registrarEventosDeAcceso(): void
    {
        Event::listen(function (Login $evento): void {
            if ($evento->user instanceof User) {
                app(RegistroEventosAplicacionService::class)->registrarIngreso($evento->user);
            }
        });

        Event::listen(function (Failed $evento): void {
            app(RegistroEventosAplicacionService::class)->registrarIngresoFallido(
                $evento->credentials,
                $evento->user instanceof User ? $evento->user : null,
            );
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        if (app()->isProduction()) {
            URL::forceScheme('https');

            // Genera rutas relativas para assets (/build/...) y evita mixed content
            // incluso si APP_URL o el proxy entregan un esquema incorrecto.
            Vite::createAssetPathsUsing(
                fn (string $path, ?bool $secure = null): string => '/'.ltrim($path, '/'),
            );
        }

        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
