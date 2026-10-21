<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TurnoSurtidor extends Model
{
    /**
     * Mangueras de la EDS en el orden de la planilla, con su combustible. Cada isla tiene cuatro.
     *
     * @var array<string, string>
     */
    public const MANGUERAS = [
        'PLUS 01' => 'corriente',
        'PLUS 02' => 'corriente',
        'ACPM 03' => 'acpm',
        'ACPM 04' => 'acpm',
        'PLUS 05' => 'corriente',
        'PLUS 06' => 'corriente',
        'ACPM 07' => 'acpm',
        'ACPM 08' => 'acpm',
        'PLUS 09' => 'corriente',
        'PLUS 10' => 'corriente',
        'ACPM 11' => 'acpm',
        'ACPM 12' => 'acpm',
    ];

    protected $table = 'turno_surtidores';

    protected $fillable = [
        'turno_id',
        'manguera',
        'combustible',
        'lectura_inicial',
        'lectura_final',
        'galones',
        'total',
    ];

    protected $casts = [
        'lectura_inicial' => 'decimal:3',
        'lectura_final' => 'decimal:3',
        'galones' => 'decimal:3',
        'total' => 'decimal:2',
    ];

    public function turno()
    {
        return $this->belongsTo(Turno::class);
    }
}
