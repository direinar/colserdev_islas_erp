<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanillaMedioPago extends Model
{
    protected $fillable = [
        'planilla_id',
        'medio',
        'valor',
        'orden',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
    ];

    public function planilla(): BelongsTo
    {
        return $this->belongsTo(Planilla::class);
    }
}
