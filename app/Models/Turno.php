<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Turno extends Model
{
    protected $fillable = [
        'fecha',
        'numero_turno',
        'nombre_vendedor',
        'revisado_por',          // tu migración usa 'revisado_por', no 'revisado'
        'precio_corriente',
        'precio_acpm',
        'traslado_sobrante',
        'traslado_faltante',
    ];

    protected $casts = [
        'fecha'            => 'date',
        'precio_corriente' => 'decimal:2',
        'precio_acpm'      => 'decimal:2',
        'traslado_sobrante'=> 'decimal:2',
        'traslado_faltante'=> 'decimal:2',
    ];

    // Relaciones — todas correctas, solo agregar las que faltaban
    public function ventas()         { return $this->hasMany(TurnoVenta::class); }
    public function surtidores()     { return $this->hasMany(TurnoSurtidor::class); }
    public function lubricantes()    { return $this->hasMany(TurnoLubricante::class); }
    public function mediosPago()     { return $this->hasMany(TurnoMedioPago::class); }
    public function qrPagos()        { return $this->hasMany(TurnoQrPago::class); }
    public function recaudos()       { return $this->hasMany(TurnoRecaudo::class); }
    public function transferencias() { return $this->hasMany(TurnoTransferencia::class); }
    public function gasolinaEds()    { return $this->hasMany(TurnoGasolinaEds::class); }
    public function varios()         { return $this->hasMany(TurnoVarios::class); }
    public function recaudosAdmin()  { return $this->hasMany(TurnoRecaudoAdmin::class); }
}
