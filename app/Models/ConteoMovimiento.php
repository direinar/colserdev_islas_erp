<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Fila concepto/valor de un conteo: retiros antes del cierre (con nombre), gastos y retiros de moneda.
 */
class ConteoMovimiento extends Model
{
    public const TIPO_RETIRO = 'retiro';

    public const TIPO_GASTO = 'gasto';

    public const TIPO_RETIRO_MONEDA = 'retiro_moneda';

    protected $fillable = ['conteo_id', 'tipo', 'nombre', 'concepto', 'valor'];

    protected $casts = ['valor' => 'decimal:2'];

    public function conteo()
    {
        return $this->belongsTo(Conteo::class);
    }
}
