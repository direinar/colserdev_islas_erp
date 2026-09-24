<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuelPrice extends Model
{
    protected $fillable = [
        'name',
        'price',
        'effective_date',
        'active',
    ];

    /**
     * Precio activo vigente para un combustible en una fecha dada.
     * Permite que cada turno quede ligado al precio que regía ese día,
     * incluso si luego se registra un cambio de precio posterior.
     */
    public static function activePriceOn(string $name, $date): ?float
    {
        $price = static::query()
            ->where('name', $name)
            ->where('active', true)
            ->whereDate('effective_date', '<=', $date)
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->value('price');

        return $price !== null ? (float) $price : null;
    }
}
