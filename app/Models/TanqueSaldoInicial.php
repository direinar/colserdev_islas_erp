<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TanqueSaldoInicial extends Model
{
    public const CORRIENTE = 'corriente';

    public const ACPM = 'acpm';

    public const COMBUSTIBLES = [self::CORRIENTE, self::ACPM];

    protected $table = 'tanques_saldos_iniciales';

    protected $fillable = [
        'combustible',
        'fecha_corte',
        'galones',
    ];

    protected $casts = [
        'fecha_corte' => 'date',
        'galones' => 'decimal:3',
    ];
}
