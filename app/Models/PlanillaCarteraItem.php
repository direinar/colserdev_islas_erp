<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanillaCarteraItem extends Model
{
    protected $fillable = [
        'planilla_id',
        'cliente',
        'valor',
        'abono_1',
        'abono_2',
        'saldo',
        'orden',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'abono_1' => 'decimal:2',
        'abono_2' => 'decimal:2',
        'saldo' => 'decimal:2',
    ];

    public function planilla(): BelongsTo
    {
        return $this->belongsTo(Planilla::class);
    }
}
