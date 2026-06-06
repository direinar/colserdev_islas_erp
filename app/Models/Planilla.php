<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\PlanillaSurtidor;
use App\Models\PlanillaMedioPago;
use App\Models\PlanillaCarteraItem;

class Planilla extends Model
{
    protected $fillable = [
        'fecha',
        'turno',
        'islero',
        'surtidores',
        'medios_pago',
        'cartera',
        'total_galones',
        'total_ventas',
        'total_recaudos',
        'total_cartera',
        'cuadre',
        'estado',
    ];

    protected $casts = [
        'fecha' => 'date',
        'surtidores' => 'array',
        'medios_pago' => 'array',
        'cartera' => 'array',
        'total_galones' => 'decimal:3',
        'total_ventas' => 'decimal:2',
        'total_recaudos' => 'decimal:2',
        'total_cartera' => 'decimal:2',
        'cuadre' => 'decimal:2',
    ];

    public function surtidores(): HasMany
    {
        return $this->hasMany(PlanillaSurtidor::class);
    }

    public function mediosPagos(): HasMany
    {
        return $this->hasMany(PlanillaMedioPago::class);
    }

    public function carteraItems(): HasMany
    {
        return $this->hasMany(PlanillaCarteraItem::class);
    }
}
