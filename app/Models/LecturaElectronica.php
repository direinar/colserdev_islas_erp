<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Lectura electrónica de una manguera en una fecha (informe Lectura electrónica).
 */
class LecturaElectronica extends Model
{
    /**
     * Mangueras de la EDS en el orden del informe, con su combustible.
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

    protected $table = 'lecturas_electronicas';

    protected $fillable = [
        'fecha',
        'manguera',
        'combustible',
        'lectura_inicial',
        'lectura_final',
        'galones',
        'user_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'lectura_inicial' => 'decimal:3',
        'lectura_final' => 'decimal:3',
        'galones' => 'decimal:3',
    ];
}
