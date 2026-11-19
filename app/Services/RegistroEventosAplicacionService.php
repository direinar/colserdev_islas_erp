<?php

namespace App\Services;

use App\Models\EventoAplicacion;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Registra las fallas y los eventos de la aplicación para la Auditoría del administrador:
 * errores del sistema (desde el manejador de excepciones), acciones de los usuarios que
 * guardan, modifican o eliminan información (middleware RegistrarEventoAplicacion) e
 * ingresos al sistema. Registrar nunca debe interrumpir al usuario: cualquier error al
 * guardar el evento se ignora.
 */
class RegistroEventosAplicacionService
{
    /**
     * Nombre legible del recurso según el nombre de la ruta (sin la acción final).
     *
     * @var array<string, string>
     */
    private const RECURSOS = [
        'turnos' => 'planilla de turno',
        'anticipo-bimestral' => 'anticipo bimestral',
        'bancos' => 'banco',
        'cartera' => 'movimiento de cartera',
        'cartera-saldos-iniciales' => 'saldo inicial de cartera',
        'compras' => 'compra de combustible',
        'compras-lubricantes' => 'compra de canastilla',
        'comprobante-contable-compras' => 'comprobante contable de compras',
        'conteos' => 'conteo',
        'conteos.destinos' => 'destino del conteo',
        'conteos.terceros' => 'tercero del conteo',
        'control-planillas' => 'alerta de planilla',
        'control-tanques' => 'control de tanques',
        'customers' => 'cliente',
        'fuel-prices' => 'precio de combustible',
        'informes.anticipo-bimestral' => 'anticipo bimestral',
        'informes.cierre' => 'contrato del cierre',
        'informes.comisiones' => 'porcentajes de comisión',
        'informes.comprobante-contable-compras' => 'cuentas del comprobante de compras',
        'informes.comprobante-recaudos' => 'cuentas del comprobante de recaudos',
        'informes.comprobante-ventas' => 'cuentas del comprobante de ventas',
        'informes.margen-mayorista' => 'parámetros del margen mayorista',
        'inventarios-ajustes' => 'ajuste de inventario',
        'inventarios-saldos-iniciales' => 'saldo inicial de inventario',
        'lectura-electronica' => 'lectura electrónica',
        'lubricants' => 'producto de canastilla',
        'nomina.empleados' => 'empleado',
        'nomina.festivos' => 'festivos',
        'nomina.parametros-liquidacion' => 'parámetros de liquidación',
        'nomina.planillas-turnos' => 'planilla de turnos de nómina',
        'nomina.turnos' => 'turno de trabajo',
        'proveedores' => 'proveedor',
        'users' => 'usuario',
    ];

    /**
     * Verbo según la acción final del nombre de la ruta.
     *
     * @var array<string, string>
     */
    private const VERBOS = [
        'store' => 'Guardó',
        'update' => 'Modificó',
        'destroy' => 'Eliminó',
        'anular' => 'Anuló',
        'reabrir' => 'Reabrió',
        'revisar' => 'Marcó como revisada',
        'cerrar' => 'Cerró',
        'importar' => 'Importó',
        'generar' => 'Generó',
        'guardar' => 'Guardó',
        'atender' => 'Atendió',
    ];

    /**
     * Acciones que el administrador debe revisar aunque hayan salido bien.
     */
    private const ACCIONES_SENSIBLES = ['destroy', 'anular', 'reabrir', 'importar', 'saldo-inicial'];

    /**
     * Recursos cuyos cambios siempre se revisan (permisos y precios).
     */
    private const RECURSOS_SENSIBLES = ['users', 'fuel-prices'];

    /**
     * Falla del sistema. La misma falla (clase, archivo y línea) suma ocurrencias mientras
     * siga sin resolver; si vuelve a ocurrir después de resuelta se registra de nuevo como
     * reincidente.
     */
    public function registrarError(Throwable $excepcion): void
    {
        try {
            $request = $this->requestHttp();
            $huella = sha1(get_class($excepcion).'|'.$excepcion->getFile().'|'.$excepcion->getLine());
            $mensaje = trim($excepcion->getMessage()) !== '' ? $excepcion->getMessage() : class_basename($excepcion);

            $anterior = EventoAplicacion::query()->where('huella', $huella)->latest('id')->first();

            if ($anterior && $anterior->estado !== EventoAplicacion::ESTADO_RESUELTO) {
                $anterior->forceFill([
                    'ocurrencias' => $anterior->ocurrencias + 1,
                    'ultima_vez' => now(),
                    'user_id' => $this->usuarioId() ?? $anterior->user_id,
                    'url' => $request ? Str::limit($request->fullUrl(), 495) : $anterior->url,
                ])->save();

                return;
            }

            EventoAplicacion::create([
                ...$this->datosRequest($request),
                'tipo' => EventoAplicacion::TIPO_ERROR,
                'nivel' => EventoAplicacion::NIVEL_CRITICO,
                'modulo' => $request ? $this->modulo($request->route()?->getName(), $request->path()) : 'Tareas del servidor',
                'titulo' => Str::limit(class_basename($excepcion).': '.Str::squish($mensaje), 250),
                'descripcion' => Str::limit($mensaje, 5000),
                'detalle' => [
                    'clase' => get_class($excepcion),
                    'archivo' => $this->rutaRelativa($excepcion->getFile()),
                    'linea' => $excepcion->getLine(),
                    'origen' => $this->origenEnLaAplicacion($excepcion),
                    'traza' => $this->traza($excepcion),
                    'reincidente' => $anterior !== null,
                ],
                'user_id' => $this->usuarioId(),
                'huella' => $huella,
                'ultima_vez' => now(),
                'estado' => EventoAplicacion::ESTADO_PENDIENTE,
            ]);
        } catch (Throwable) {
            // Sin base de datos no hay dónde registrar: la falla queda en el log de Laravel.
        }
    }

    /**
     * Acción de un usuario que guarda, modifica o elimina información. Las acciones
     * sensibles (eliminar, anular, reabrir, usuarios, precios…) y las rechazadas por
     * permisos quedan como advertencia pendiente; las demás solo se registran.
     */
    public function registrarAccion(Request $request, Response $response): void
    {
        try {
            $nombreRuta = (string) $request->route()?->getName();
            $partes = explode('.', $nombreRuta);
            $accion = count($partes) > 1 ? array_pop($partes) : 'store';
            $recurso = implode('.', $partes);
            $estadoHttp = $response->getStatusCode();

            $titulo = trim((self::VERBOS[$accion] ?? Str::ucfirst(str_replace('-', ' ', $accion)))
                .' '.(self::RECURSOS[$recurso] ?? str_replace(['-', '.'], ' ', $recurso))
                .' '.$this->identificador($request));

            // Solo cuentan los errores que dejó esta misma solicitud (no los de la anterior).
            $rechazadaPorValidacion = $estadoHttp === 422
                || ($request->hasSession() && $estadoHttp < 400 && in_array('errors', (array) $request->session()->get('_flash.new', []), true));
            $sinPermiso = in_array($estadoHttp, [401, 403, 404, 405], true);
            $sensible = in_array($accion, self::ACCIONES_SENSIBLES, true)
                || in_array($partes[0] ?? '', self::RECURSOS_SENSIBLES, true);

            if ($sinPermiso) {
                $titulo = 'Intento sin permiso: '.Str::lcfirst($titulo);
            } elseif ($rechazadaPorValidacion) {
                $titulo .= ' (no se guardó: datos con errores)';
            }

            $advertencia = $sinPermiso || ($sensible && ! $rechazadaPorValidacion);

            EventoAplicacion::create([
                ...$this->datosRequest($request),
                'tipo' => EventoAplicacion::TIPO_ACCION,
                'nivel' => $advertencia ? EventoAplicacion::NIVEL_ADVERTENCIA : EventoAplicacion::NIVEL_INFORMATIVO,
                'modulo' => $this->modulo($nombreRuta, $request->path()),
                'titulo' => Str::limit($titulo, 250),
                'descripcion' => $rechazadaPorValidacion && $request->hasSession()
                    ? (Str::limit(implode(' ', $request->session()->get('errors')?->all() ?? []), 2000) ?: null)
                    : null,
                'estado_http' => $estadoHttp,
                'user_id' => $request->user()?->id,
                'ultima_vez' => now(),
                'estado' => $advertencia ? EventoAplicacion::ESTADO_PENDIENTE : EventoAplicacion::ESTADO_REGISTRADO,
            ]);
        } catch (Throwable) {
            // Registrar el evento nunca debe afectar la respuesta al usuario.
        }
    }

    public function registrarIngreso(User $usuario): void
    {
        try {
            EventoAplicacion::create([
                ...$this->datosRequest($this->requestHttp()),
                'tipo' => EventoAplicacion::TIPO_ACCESO,
                'nivel' => EventoAplicacion::NIVEL_INFORMATIVO,
                'modulo' => 'Acceso',
                'titulo' => 'Inició sesión',
                'user_id' => $usuario->id,
                'ultima_vez' => now(),
                'estado' => EventoAplicacion::ESTADO_REGISTRADO,
            ]);
        } catch (Throwable) {
            //
        }
    }

    /**
     * Intento fallido de ingreso: los del mismo usuario en el mismo día suman ocurrencias.
     * Nunca se guarda la contraseña.
     *
     * @param  array<string, mixed>  $credenciales
     */
    public function registrarIngresoFallido(array $credenciales, ?User $usuario): void
    {
        try {
            $cuenta = Str::lower((string) ($credenciales['email'] ?? $credenciales['username'] ?? 'desconocido'));
            $huella = sha1('ingreso-fallido|'.$cuenta.'|'.now()->toDateString());
            $request = $this->requestHttp();

            $anterior = EventoAplicacion::query()->where('huella', $huella)->first();

            if ($anterior) {
                $anterior->forceFill([
                    'ocurrencias' => $anterior->ocurrencias + 1,
                    'ultima_vez' => now(),
                    'ip' => $request?->ip() ?? $anterior->ip,
                    // Si ya se había gestionado y vuelve a fallar, se revisa otra vez.
                    'estado' => $anterior->porGestionar() ? $anterior->estado : EventoAplicacion::ESTADO_PENDIENTE,
                ])->save();

                return;
            }

            EventoAplicacion::create([
                ...$this->datosRequest($request),
                'tipo' => EventoAplicacion::TIPO_ACCESO,
                'nivel' => EventoAplicacion::NIVEL_ADVERTENCIA,
                'modulo' => 'Acceso',
                'titulo' => Str::limit('Intento fallido de ingreso con '.$cuenta, 250),
                'descripcion' => $usuario ? 'La cuenta existe: contraseña incorrecta.' : 'No existe un usuario con esa cuenta.',
                'user_id' => $usuario?->id,
                'huella' => $huella,
                'ultima_vez' => now(),
                'estado' => EventoAplicacion::ESTADO_PENDIENTE,
            ]);
        } catch (Throwable) {
            //
        }
    }

    /**
     * Cambia el estado de una falla o advertencia y deja la gestión en su historial.
     */
    public function gestionar(EventoAplicacion $evento, string $estado, ?string $observacion, User $usuario): void
    {
        $detalle = $evento->detalle ?? [];
        $detalle['gestiones'][] = [
            'estado' => $estado,
            'observacion' => $observacion,
            'usuario' => $usuario->name,
            'fecha' => now()->format('Y-m-d H:i'),
        ];

        $evento->update([
            'estado' => $estado,
            'observacion' => $observacion,
            'gestionado_por' => $usuario->id,
            'gestionado_at' => now(),
            'detalle' => $detalle,
        ]);
    }

    /**
     * Cuántos eventos hay de cada color del semáforo. Rojo y amarillo cuentan todo lo que
     * espera gestión (de cualquier fecha); verde y gris, lo del periodo.
     *
     * @return array{rojo: int, amarillo: int, verde: int, gris: int}
     */
    public function semaforo(string $desde, string $hasta): array
    {
        $delPeriodo = fn (string $color): int => EventoAplicacion::query()->semaforo($color)
            ->whereBetween('ultima_vez', [$desde.' 00:00:00', $hasta.' 23:59:59'])->count();

        return [
            'rojo' => EventoAplicacion::query()->semaforo('rojo')->count(),
            'amarillo' => EventoAplicacion::query()->semaforo('amarillo')->count(),
            'verde' => $delPeriodo('verde'),
            'gris' => $delPeriodo('gris'),
        ];
    }

    public function modulo(?string $nombreRuta, string $ruta): string
    {
        $nombre = (string) $nombreRuta;

        return match (true) {
            str_starts_with($nombre, 'nomina.') || str_starts_with($ruta, 'nomina') => 'Nómina',
            str_starts_with($nombre, 'contabilidad.') || str_starts_with($ruta, 'contabilidad') => 'Contabilidad',
            default => 'Operaciones',
        };
    }

    /**
     * @return array{metodo: ?string, url: ?string, ruta: ?string, ip: ?string}
     */
    private function datosRequest(?Request $request): array
    {
        return [
            'metodo' => $request?->method(),
            'url' => $request ? Str::limit($request->fullUrl(), 495) : null,
            'ruta' => $request?->route()?->getName(),
            'ip' => $request?->ip(),
        ];
    }

    /**
     * Solicitud web en curso (null en tareas de consola).
     */
    private function requestHttp(): ?Request
    {
        if (! app()->bound('request')) {
            return null;
        }

        $request = app('request');

        return $request->route() !== null || ! app()->runningInConsole() ? $request : null;
    }

    private function usuarioId(): ?int
    {
        try {
            return auth()->id();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Número del registro afectado: el No. de la planilla de turno o el id del modelo.
     */
    private function identificador(Request $request): string
    {
        $parametro = collect($request->route()?->parameters() ?? [])->first();

        if ($parametro instanceof Model) {
            return '#'.($parametro->getAttribute('numero_turno') ?? $parametro->getKey());
        }

        if (is_scalar($parametro)) {
            return '#'.$parametro;
        }

        $numero = $request->input('numero_turno');

        return is_scalar($numero) && $numero !== '' ? '#'.$numero : '';
    }

    private function rutaRelativa(string $archivo): string
    {
        return ltrim(str_replace([base_path(), '\\'], ['', '/'], $archivo), '/');
    }

    /**
     * Primer punto del código propio (fuera de vendor) donde se originó la falla.
     */
    private function origenEnLaAplicacion(Throwable $excepcion): ?string
    {
        $marcos = [['file' => $excepcion->getFile(), 'line' => $excepcion->getLine()], ...$excepcion->getTrace()];

        foreach ($marcos as $marco) {
            $archivo = $this->rutaRelativa((string) ($marco['file'] ?? ''));
            if ($archivo !== '' && ! str_starts_with($archivo, 'vendor/') && ! str_contains($archivo, 'storage/framework')) {
                return $archivo.':'.($marco['line'] ?? '?');
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function traza(Throwable $excepcion): array
    {
        return collect($excepcion->getTrace())
            ->take(15)
            ->map(fn (array $marco): string => $this->rutaRelativa((string) ($marco['file'] ?? '[interno]'))
                .':'.($marco['line'] ?? '?').' '.($marco['class'] ?? '').($marco['type'] ?? '').($marco['function'] ?? ''))
            ->values()
            ->all();
    }
}
