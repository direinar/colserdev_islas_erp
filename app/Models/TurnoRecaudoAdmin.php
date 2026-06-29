<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TurnoRecaudoAdmin extends Model
{
    protected $table = 'turno_recaudos_admin';

    protected $fillable = [
        'turno_id',
        'banco',
        'responsable_id',
        'valor',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
    ];

    public function turno()
    {
        return $this->belongsTo(Turno::class);
    }

    public function responsable()
    {
        return $this->belongsTo(Customer::class, 'responsable_id');
    }
}
