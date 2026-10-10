<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiquidacionAnticipoBimestral extends Model
{
    protected $table = 'liquidaciones_anticipo_bimestral';

    protected $fillable = [
        'anio',
        'bimestre',
        'otros_ingresos',
        'descuentos_ventas',
    ];

    protected $casts = [
        'anio' => 'integer',
        'bimestre' => 'integer',
        'otros_ingresos' => 'decimal:0',
        'descuentos_ventas' => 'decimal:0',
    ];
}
