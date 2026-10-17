<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Compra de combustible por factura. Por cada combustible: galones, bases del 19% y del 5%,
 * IVA liquidado, costo del producto, sobretasa y Soldicom; distribucion_* es el COSTO
 * (IVA + producto + sobretasa + Soldicom) y es lo que alimenta los inventarios.
 */
class Compra extends Model
{
    public const COMBUSTIBLES = ['gasolina', 'acpm'];

    public const TARIFA_IVA_GENERAL = 0.19;

    public const TARIFA_IVA_REDUCIDA = 0.05;

    protected $fillable = [
        'fecha',
        'factura',
        'proveedor_id',
        'vr_total_fra',
        'gasolina',
        'acpm',
        'total',
        'distribucion_gasolina',
        'distribucion_acpm',
        'base19_gasolina',
        'base5_gasolina',
        'iva_gasolina',
        'costo_producto_gasolina',
        'sobretasa_gasolina',
        'soldicom_gasolina',
        'base19_acpm',
        'base5_acpm',
        'iva_acpm',
        'costo_producto_acpm',
        'sobretasa_acpm',
        'soldicom_acpm',
    ];

    protected $casts = [
        'fecha' => 'date',
        'vr_total_fra' => 'decimal:2',
        'gasolina' => 'decimal:3',
        'acpm' => 'decimal:3',
        'total' => 'decimal:3',
        'distribucion_gasolina' => 'decimal:2',
        'distribucion_acpm' => 'decimal:2',
        'base19_gasolina' => 'decimal:2',
        'base5_gasolina' => 'decimal:2',
        'iva_gasolina' => 'decimal:2',
        'costo_producto_gasolina' => 'decimal:2',
        'sobretasa_gasolina' => 'decimal:2',
        'soldicom_gasolina' => 'decimal:2',
        'base19_acpm' => 'decimal:2',
        'base5_acpm' => 'decimal:2',
        'iva_acpm' => 'decimal:2',
        'costo_producto_acpm' => 'decimal:2',
        'sobretasa_acpm' => 'decimal:2',
        'soldicom_acpm' => 'decimal:2',
    ];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    /**
     * Costo unitario del combustible: COSTO / GALONES.
     */
    public function costoUnitario(string $combustible): float
    {
        $galones = (float) $this->{$combustible};

        return $galones > 0 ? (float) $this->{'distribucion_'.$combustible} / $galones : 0.0;
    }
}
