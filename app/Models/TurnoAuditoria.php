<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Un guardado (o anulación) de una planilla de turno con el resumen de sus secciones. Con
 * alerta cuando el guardado quitó filas o bajó el total de ventas.
 */
class TurnoAuditoria extends Model
{
    public const ACCION_CREADA = 'creada';

    public const ACCION_GUARDADA = 'guardada';

    public const ACCION_ANULADA = 'anulada';

    protected $table = 'turno_auditorias';

    protected $fillable = [
        'turno_id',
        'numero_turno',
        'accion',
        'resumen',
        'alerta',
        'atendida_at',
        'atendida_por',
        'user_id',
    ];

    protected $casts = [
        'resumen' => 'array',
        'atendida_at' => 'datetime',
    ];

    public function turno()
    {
        return $this->belongsTo(Turno::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Alertas que nadie ha revisado todavía.
     *
     * @param  Builder<TurnoAuditoria>  $query
     */
    public function scopePendientes(Builder $query): void
    {
        $query->whereNotNull('alerta')->whereNull('atendida_at');
    }
}
