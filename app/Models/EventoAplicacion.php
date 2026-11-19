<?php

namespace App\Models;

use Database\Factories\EventoAplicacionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Falla o evento de la aplicación (Auditoría del administrador → Fallas y eventos).
 * Las fallas y advertencias quedan PENDIENTES hasta que el administrador las gestiona;
 * los eventos informativos solo quedan REGISTRADOS (historial).
 */
class EventoAplicacion extends Model
{
    /** @use HasFactory<EventoAplicacionFactory> */
    use HasFactory, MassPrunable;

    public const TIPO_ERROR = 'error';

    public const TIPO_ACCION = 'accion';

    public const TIPO_ACCESO = 'acceso';

    public const NIVEL_CRITICO = 'critico';

    public const NIVEL_ADVERTENCIA = 'advertencia';

    public const NIVEL_INFORMATIVO = 'informativo';

    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_EN_REVISION = 'en_revision';

    public const ESTADO_RESUELTO = 'resuelto';

    public const ESTADO_DESCARTADO = 'descartado';

    /**
     * Evento informativo: no requiere gestión.
     */
    public const ESTADO_REGISTRADO = 'registrado';

    /**
     * @var array<string, string>
     */
    public const TIPOS = [
        self::TIPO_ERROR => 'Falla del sistema',
        self::TIPO_ACCION => 'Acción de usuario',
        self::TIPO_ACCESO => 'Ingreso al sistema',
    ];

    /**
     * @var array<string, string>
     */
    public const NIVELES = [
        self::NIVEL_CRITICO => 'Crítico',
        self::NIVEL_ADVERTENCIA => 'Advertencia',
        self::NIVEL_INFORMATIVO => 'Informativo',
    ];

    /**
     * Estados que el administrador puede asignar al gestionar.
     *
     * @var array<string, string>
     */
    public const ESTADOS_GESTION = [
        self::ESTADO_PENDIENTE => 'Pendiente',
        self::ESTADO_EN_REVISION => 'En revisión',
        self::ESTADO_RESUELTO => 'Resuelto',
        self::ESTADO_DESCARTADO => 'Descartado',
    ];

    /**
     * Semáforo: rojo = falla crítica sin atender; amarillo = advertencia sin atender o en
     * revisión; verde = resuelto o sin novedad; gris = descartado.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    public const SEMAFOROS = [
        'rojo' => ['Fallas críticas sin atender', 'Requieren atención inmediata.'],
        'amarillo' => ['Advertencias y en revisión', 'Pendientes de revisar o en curso.'],
        'verde' => ['Resueltos y eventos', 'Gestionados o sin novedad.'],
        'gris' => ['Descartados', 'Revisados sin acción.'],
    ];

    protected $table = 'eventos_aplicacion';

    protected $fillable = [
        'tipo',
        'nivel',
        'modulo',
        'titulo',
        'descripcion',
        'detalle',
        'metodo',
        'url',
        'ruta',
        'estado_http',
        'ip',
        'user_id',
        'huella',
        'ocurrencias',
        'ultima_vez',
        'estado',
        'gestionado_por',
        'gestionado_at',
        'observacion',
    ];

    protected $casts = [
        'detalle' => 'array',
        'ultima_vez' => 'datetime',
        'gestionado_at' => 'datetime',
        'ocurrencias' => 'integer',
        'estado_http' => 'integer',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function gestor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gestionado_por');
    }

    public function semaforo(): string
    {
        return match (true) {
            $this->estado === self::ESTADO_DESCARTADO => 'gris',
            in_array($this->estado, [self::ESTADO_RESUELTO, self::ESTADO_REGISTRADO], true) => 'verde',
            $this->estado === self::ESTADO_PENDIENTE && $this->nivel === self::NIVEL_CRITICO => 'rojo',
            default => 'amarillo',
        };
    }

    public function porGestionar(): bool
    {
        return in_array($this->estado, [self::ESTADO_PENDIENTE, self::ESTADO_EN_REVISION], true);
    }

    /**
     * Eventos de un color del semáforo (misma regla que semaforo()).
     *
     * @param  Builder<EventoAplicacion>  $query
     */
    public function scopeSemaforo(Builder $query, string $color): void
    {
        match ($color) {
            'rojo' => $query->where('estado', self::ESTADO_PENDIENTE)->where('nivel', self::NIVEL_CRITICO),
            'amarillo' => $query->where(function (Builder $query): void {
                $query->where('estado', self::ESTADO_EN_REVISION)
                    ->orWhere(fn (Builder $query) => $query->where('estado', self::ESTADO_PENDIENTE)->where('nivel', '!=', self::NIVEL_CRITICO));
            }),
            'verde' => $query->whereIn('estado', [self::ESTADO_RESUELTO, self::ESTADO_REGISTRADO]),
            'gris' => $query->where('estado', self::ESTADO_DESCARTADO),
            default => null,
        };
    }

    /**
     * Fallas y advertencias que esperan gestión (pendientes o en revisión).
     *
     * @param  Builder<EventoAplicacion>  $query
     */
    public function scopePorGestionar(Builder $query): void
    {
        $query->whereIn('estado', [self::ESTADO_PENDIENTE, self::ESTADO_EN_REVISION]);
    }

    /**
     * Los eventos informativos (ingresos y acciones sin novedad) se borran a los 180 días
     * con `php artisan model:prune`. Las fallas y advertencias se conservan.
     *
     * @return Builder<EventoAplicacion>
     */
    public function prunable(): Builder
    {
        return static::query()
            ->where('estado', self::ESTADO_REGISTRADO)
            ->where('created_at', '<', now()->subDays(180));
    }
}
