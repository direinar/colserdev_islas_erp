<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TurnoGasolinaEds extends Model
{
    protected $fillable = [
        'turno_id',
        'valor',
        'total',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function turno()
    {
        return $this->belongsTo(Turno::class);
    }
}
