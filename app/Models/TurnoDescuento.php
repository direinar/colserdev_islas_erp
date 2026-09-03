<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TurnoDescuento extends Model
{
    protected $table = 'turno_descuentos';

    protected $fillable = ['turno_id', 'descuento_no', 'valor', 'total'];

    protected $casts = ['valor' => 'decimal:2', 'total' => 'decimal:2'];

    public function turno()
    {
        return $this->belongsTo(Turno::class);
    }
}
