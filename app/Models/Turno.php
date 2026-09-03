<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Turno extends Model
{
    protected $fillable = [
        'fecha',
        'numero_turno',
        'nombre_vendedor',
        'revisado_por',          // nombre del administrador que revisó la planilla
        'revisado',
        'revisado_at',
        'precio_corriente',
        'precio_acpm',
        'tirillas_galones_corriente',
        'tirillas_galones_acpm',
        'tirillas_valor_corriente',
        'tirillas_valor_acpm',
        'total_ventas',
        'lecturas_galones_corriente',
        'lecturas_galones_acpm',
        'lecturas_valor_corriente',
        'lecturas_valor_acpm',
        'total_venta_lecturas',
    ];

    protected $casts = [
        'fecha' => 'date',
        'revisado' => 'boolean',
        'revisado_at' => 'datetime',
        'precio_corriente' => 'decimal:2',
        'precio_acpm' => 'decimal:2',
        'tirillas_galones_corriente' => 'decimal:3',
        'tirillas_galones_acpm' => 'decimal:3',
        'tirillas_valor_corriente' => 'decimal:2',
        'tirillas_valor_acpm' => 'decimal:2',
        'total_ventas' => 'decimal:2',
        'lecturas_galones_corriente' => 'decimal:3',
        'lecturas_galones_acpm' => 'decimal:3',
        'lecturas_valor_corriente' => 'decimal:2',
        'lecturas_valor_acpm' => 'decimal:2',
        'total_venta_lecturas' => 'decimal:2',
    ];

    // Relaciones — todas correctas, solo agregar las que faltaban
    public function ventas()
    {
        return $this->hasMany(TurnoVenta::class);
    }

    public function surtidores()
    {
        return $this->hasMany(TurnoSurtidor::class);
    }

    public function lubricantes()
    {
        return $this->hasMany(TurnoLubricante::class);
    }

    public function mediosPago()
    {
        return $this->hasMany(TurnoMedioPago::class);
    }

    public function consignaciones()
    {
        return $this->hasMany(TurnoConsignacion::class);
    }

    public function descuentos()
    {
        return $this->hasMany(TurnoDescuento::class);
    }

    public function cartera()
    {
        return $this->hasMany(TurnoCartera::class);
    }

    public function qrPagos()
    {
        return $this->hasMany(TurnoQrPago::class);
    }

    public function recaudos()
    {
        return $this->hasMany(TurnoRecaudo::class);
    }

    public function transferencias()
    {
        return $this->hasMany(TurnoTransferencia::class);
    }

    public function gasolinaEds()
    {
        return $this->hasMany(TurnoGasolinaEds::class);
    }

    public function varios()
    {
        return $this->hasMany(TurnoVarios::class);
    }

    public function recaudosAdmin()
    {
        return $this->hasMany(TurnoRecaudoAdmin::class);
    }
}
