<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TurnoVenta extends Model
{
    protected $fillable = [
        'turno_id',
        'surtidor',
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
