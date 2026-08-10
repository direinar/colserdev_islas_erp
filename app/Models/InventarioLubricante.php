<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventarioLubricante extends Model
{
    protected $table = 'inventarios_lubricantes';

    protected $fillable = [
        'producto',
        'fecha',
        'planilla_no',
        'fc_no',
        'proveedor',
        'entradas_unidades',
        'salidas_unidades',
        'saldo_unidades',
        'valor_entradas',
        'valor_salidas',
        'valor_saldo',
        'costo_promedio',
        'vr_venta',
        'precio_venta',
        'saldo_anterior_unidades',
        'saldo_anterior_valor',
        'saldo_anterior_promedio',
    ];

    protected $casts = [
        'fecha' => 'date',
        'entradas_unidades' => 'decimal:3',
        'salidas_unidades' => 'decimal:3',
        'saldo_unidades' => 'decimal:3',
        'valor_entradas' => 'decimal:2',
        'valor_salidas' => 'decimal:2',
        'valor_saldo' => 'decimal:2',
        'costo_promedio' => 'decimal:4',
        'vr_venta' => 'decimal:2',
        'precio_venta' => 'decimal:2',
        'saldo_anterior_unidades' => 'decimal:3',
        'saldo_anterior_valor' => 'decimal:2',
        'saldo_anterior_promedio' => 'decimal:4',
    ];
}
