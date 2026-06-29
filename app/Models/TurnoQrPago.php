<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TurnoQrPago extends Model
{
    protected $fillable = [
        'turno_id',
        'concepto',
        'valor',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
    ];

    public function turno()
    {
        return $this->belongsTo(Turno::class);
    }
}
