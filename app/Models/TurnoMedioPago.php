<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TurnoMedioPago extends Model
{
    protected $table = 'turno_medios_pago';

    protected $fillable = [
        'turno_id',
        'consignacion_no',
        'consignacion_valor',
        'descuento',
        'cartera_factura_no',
        'cliente_id',
        'cartera_valor',
    ];

    protected $casts = [
        'consignacion_valor' => 'decimal:2',
        'descuento' => 'decimal:2',
        'cartera_valor' => 'decimal:2',
    ];

    public function turno()
    {
        return $this->belongsTo(Turno::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Customer::class, 'cliente_id');
    }
}
