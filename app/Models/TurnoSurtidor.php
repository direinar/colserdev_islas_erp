<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TurnoSurtidor extends Model
{
    protected $table = 'turno_surtidores';
    protected $fillable = [
        'turno_id',
        'manguera',
        'combustible',
        'lectura_inicial',
        'lectura_final',
        'galones',
    ];

    protected $casts = [
        'lectura_inicial' => 'decimal:3',
        'lectura_final' => 'decimal:3',
        'galones' => 'decimal:3',
    ];

    public function turno()
    {
        return $this->belongsTo(Turno::class);
    }
}
