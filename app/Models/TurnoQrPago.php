<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TurnoQrPago extends Model
{
    /**
     * Conceptos de pagos electrónicos y transferencias que admite la planilla.
     */
    public const CONCEPTOS = ['TC', 'QR', 'NEQUI', 'DAVIPLATA', 'TRANSFERENCIA'];

    protected $fillable = [
        'turno_id',
        'concepto',
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
