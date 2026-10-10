<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GalonajeContrato extends Model
{
    protected $table = 'galonaje_contrato';

    protected $fillable = [
        'fecha_corte',
        'galonaje_acumulado_inicial',
        'saldo_contrato_inicial',
    ];

    protected $casts = [
        'fecha_corte' => 'date',
        'galonaje_acumulado_inicial' => 'decimal:3',
        'saldo_contrato_inicial' => 'decimal:3',
    ];
}
