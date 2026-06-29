<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TurnoLubricante extends Model
{
    protected $fillable = [
        'turno_id',
        'cantidad',
        'producto',
        'valor_sin_iva',
        'iva',
        'total',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'valor_sin_iva' => 'decimal:2',
        'iva' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function turno()
    {
        return $this->belongsTo(Turno::class);
    }
}
