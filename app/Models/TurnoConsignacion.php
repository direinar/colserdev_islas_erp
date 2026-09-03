<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TurnoConsignacion extends Model
{
    protected $table = 'turno_consignaciones';

    protected $fillable = ['turno_id', 'consignacion_no', 'valor', 'total'];

    protected $casts = ['valor' => 'decimal:2', 'total' => 'decimal:2'];

    public function turno()
    {
        return $this->belongsTo(Turno::class);
    }
}
