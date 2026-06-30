<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarteraMovimiento extends Model
{
    protected $table = 'cartera_movimientos';

    protected $fillable = [
        'customer_id',
        'saldo_inicial',
        'fecha_inicial',
        'fecha_final',
        'planillas',
        'fecha',
        'factura',
        'placas',
        'producto',
        'galones',
        'vr_unitario',
        'bruto',
        'descuento',
        'vr_neto_cargo',
        'abonos',
        'saldo',
        'cuenta',
        'concepto',
        'tercero',
        'nit',
        'debito',
        'credito',
    ];

    protected $casts = [
        'saldo_inicial' => 'decimal:0',
        'fecha_inicial' => 'date',
        'fecha_final' => 'date',
        'fecha' => 'date',
        'galones' => 'decimal:3',
        'vr_unitario' => 'decimal:0',
        'bruto' => 'decimal:0',
        'descuento' => 'decimal:0',
        'vr_neto_cargo' => 'decimal:0',
        'abonos' => 'decimal:0',
        'saldo' => 'decimal:0',
        'debito' => 'decimal:0',
        'credito' => 'decimal:0',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
