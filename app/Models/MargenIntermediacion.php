<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MargenIntermediacion extends Model
{
    protected $table = 'margenes_intermediacion';

    protected $fillable = [
        'vigente_desde',
        'valor',
    ];

    protected $casts = [
        'vigente_desde' => 'date',
        'valor' => 'decimal:2',
    ];
}
