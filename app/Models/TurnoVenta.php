<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TurnoVenta extends Model
{
    /**
     * Surtidores de la planilla (ventas según cierres de IAPROPIADA) en el orden de la
     * pantalla, con su combustible. Cada uno admite varios cierres en el turno.
     *
     * @var array<string, string>
     */
    public const SURTIDORES = [
        'SURTIDOR 1 CTE' => 'CTE',
        'SURTIDOR 1 ACPM' => 'ACPM',
        'SURTIDOR 2 CTE' => 'CTE',
        'SURTIDOR 2 ACPM' => 'ACPM',
        'SURTIDOR 3 CTE' => 'CTE',
        'SURTIDOR 3 ACPM' => 'ACPM',
    ];

    protected $fillable = [
        'turno_id',
        'surtidor',
        'no_cierre',
        'combustible',
        'galones',
        'valor',
        'total',
    ];

    protected $casts = [
        'galones' => 'decimal:3',
        'valor' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function turno()
    {
        return $this->belongsTo(Turno::class);
    }
}
