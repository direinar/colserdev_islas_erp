<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComprobanteContableCompra extends Model
{
    protected $table = 'comprobantes_contables_compras';

    protected $fillable = [
        'fecha_inicial',
        'fecha_final',
        'cuenta',
        'concepto',
        'tercero',
        'nit',
        'debito',
        'credito',
    ];

    protected $casts = [
        'fecha_inicial' => 'date',
        'fecha_final' => 'date',
        'debito' => 'decimal:0',
        'credito' => 'decimal:0',
    ];
}
