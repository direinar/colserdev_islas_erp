<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Registro de quién cambió una lectura ya tomada por otro turno o autorizó un cambio de contador.
 */
class LecturaCorreccion extends Model
{
    /** Lectura final corregida que el turno siguiente ya había tomado como inicial. */
    public const TIPO_CORRECCION = 'correccion';

    /** Cambio o reinicio del contador de la manguera. */
    public const TIPO_CAMBIO_CONTADOR = 'cambio_contador';

    protected $table = 'lecturas_correcciones';

    protected $fillable = [
        'tipo',
        'turno_id',
        'turno_siguiente_id',
        'manguera',
        'valor_anterior',
        'valor_nuevo',
        'contador_retiro',
        'contador_instalacion',
        'observacion',
        'user_id',
    ];

    protected $casts = [
        'valor_anterior' => 'decimal:3',
        'valor_nuevo' => 'decimal:3',
        'contador_retiro' => 'decimal:3',
        'contador_instalacion' => 'decimal:3',
    ];

    public function turno()
    {
        return $this->belongsTo(Turno::class);
    }

    public function turnoSiguiente()
    {
        return $this->belongsTo(Turno::class, 'turno_siguiente_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
