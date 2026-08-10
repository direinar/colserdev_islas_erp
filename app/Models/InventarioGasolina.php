<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventarioGasolina extends Model
{
    protected $table = 'inventarios_gasolina';

    protected $fillable = [
        'fecha',
        'planilla_no',
        'fc_compra_no',
        'entradas_galones',
        'salidas_galones',
        'saldo_galones',
        'valor_entradas',
        'valor_salidas',
        'valor_saldo',
        'costo_promedio',
        'vr_venta',
        'precio_venta',
        'saldo_anterior_galones',
        'saldo_anterior_valor',
        'saldo_anterior_promedio',
    ];

    protected $casts = [
        'fecha' => 'date',
        'entradas_galones' => 'decimal:3',
        'salidas_galones' => 'decimal:3',
        'saldo_galones' => 'decimal:3',
        'valor_entradas' => 'decimal:2',
        'valor_salidas' => 'decimal:2',
        'valor_saldo' => 'decimal:2',
        'costo_promedio' => 'decimal:4',
        'vr_venta' => 'decimal:2',
        'precio_venta' => 'decimal:2',
        'saldo_anterior_galones' => 'decimal:3',
        'saldo_anterior_valor' => 'decimal:2',
        'saldo_anterior_promedio' => 'decimal:4',
    ];
}
