<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnticipoBimestral extends Model
{
    protected $table = 'anticipos_bimestrales';

    protected $fillable = [
        'bimestre',
        'periodo',
        'mes',
        'galones',
        'valor_intermediacion',
        'pesos',
    ];

    protected $casts = [
        'bimestre' => 'integer',
        'galones' => 'decimal:3',
        'valor_intermediacion' => 'decimal:2',
        'pesos' => 'decimal:0',
    ];
}
