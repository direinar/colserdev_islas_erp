<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TurnoRecaudo extends Model
{
    protected $fillable = [
        'turno_id',
        'cliente_id',
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

    public function cliente()
    {
        return $this->belongsTo(Customer::class, 'cliente_id');
    }
}
