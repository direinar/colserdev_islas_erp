<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuelPrice extends Model
{
    protected $fillable = [
        'name',
        'price',
        'turno_desde',
        'effective_date',
        'active',
    ];

    protected $casts = [
        'turno_desde' => 'integer',
    ];

    /**
     * Precio activo para un combustible en una planilla. Rige desde un número de turno
     * (consecutivo global) y no desde una fecha, porque en un mismo día el precio puede
     * cambiar entre un turno y otro.
     */
    public static function precioParaTurno(string $name, int $numeroTurno): ?float
    {
        $price = static::query()
            ->where('name', $name)
            ->where('active', true)
            ->where('turno_desde', '<=', $numeroTurno)
            ->orderByDesc('turno_desde')
            ->orderByDesc('id')
            ->value('price');

        return $price !== null ? (float) $price : null;
    }
}
