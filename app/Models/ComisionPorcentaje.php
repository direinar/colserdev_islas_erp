<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Porcentajes de comisión de los isleros (una sola fila): combustible y canastilla.
 */
class ComisionPorcentaje extends Model
{
    protected $fillable = ['combustible', 'canastilla'];

    protected $casts = [
        'combustible' => 'decimal:3',
        'canastilla' => 'decimal:3',
    ];

    /**
     * Los porcentajes vigentes; en 0 mientras administración no los registre.
     */
    public static function vigentes(): self
    {
        return self::query()->first() ?? new self(['combustible' => 0, 'canastilla' => 0]);
    }
}
