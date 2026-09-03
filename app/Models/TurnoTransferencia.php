<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TurnoTransferencia extends Model
{
    protected $fillable = [
        'turno_id',
        'puntos_redimidos',
        'total_puntos',
    ];

    protected $casts = [
        'puntos_redimidos' => 'decimal:2',
        'total_puntos' => 'decimal:2',
    ];

    public function turno()
    {
        return $this->belongsTo(Turno::class);
    }
}
