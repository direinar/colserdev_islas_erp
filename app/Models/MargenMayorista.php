<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MargenMayorista extends Model
{
    protected $table = 'margenes_mayorista';

    protected $fillable = [
        'vigente_desde',
        'porcentaje',
        'margen',
    ];

    protected $casts = [
        'vigente_desde' => 'date',
        'porcentaje' => 'decimal:4',
        'margen' => 'decimal:2',
    ];
}
