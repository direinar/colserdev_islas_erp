<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanillaSurtidor extends Model
{
    protected $fillable = [
        'planilla_id',
        'nombre',
        'producto',
        'lectura_inicial',
        'lectura_final',
        'precio',
        'galones',
        'venta',
        'orden',
    ];

    protected $casts = [
        'lectura_inicial' => 'decimal:3',
        'lectura_final' => 'decimal:3',
        'precio' => 'decimal:2',
        'galones' => 'decimal:3',
        'venta' => 'decimal:2',
    ];

    public function planilla(): BelongsTo
    {
        return $this->belongsTo(Planilla::class);
    }
}
