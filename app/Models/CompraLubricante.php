<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompraLubricante extends Model
{
    protected $table = 'compras_lubricantes';

    protected $fillable = [
        'fecha',
        'proveedor_id',
        'nombre',
        'no_fc',
        'unidades',
        'valor_unitario',
        'vr_sin_iva',
        'iva',
        'total',
    ];

    protected $casts = [
        'fecha' => 'date',
        'unidades' => 'decimal:3',
        'valor_unitario' => 'decimal:2',
        'vr_sin_iva' => 'decimal:0',
        'iva' => 'decimal:0',
        'total' => 'decimal:0',
    ];

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }
}
