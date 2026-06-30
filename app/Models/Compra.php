<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Compra extends Model
{
    protected $fillable = [
        'fecha',
        'factura',
        'vr_total_fra',
        'gasolina',
        'acpm',
        'total',
        'distribucion_gasolina',
        'distribucion_acpm',
    ];

    protected $casts = [
        'fecha' => 'date',
        'vr_total_fra' => 'decimal:2',
        'gasolina' => 'decimal:3',
        'acpm' => 'decimal:3',
        'total' => 'decimal:3',
        'distribucion_gasolina' => 'decimal:2',
        'distribucion_acpm' => 'decimal:2',
    ];
}
