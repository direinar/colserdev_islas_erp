<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TurnoTransferencia extends Model
{
    protected $fillable = [
        'turno_id',
        'valor',
        'puntos_redimidos',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'puntos_redimidos' => 'decimal:2',
    ];

    public function turno()
    {
        return $this->belongsTo(Turno::class);
    }
}
