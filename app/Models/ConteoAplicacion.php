<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Consignación en bancos y otras aplicaciones del dinero a consignar del conteo.
 */
class ConteoAplicacion extends Model
{
    protected $table = 'conteo_aplicaciones';

    protected $fillable = ['conteo_id', 'conteo_destino_id', 'conteo_tercero_id', 'valor'];

    protected $casts = ['valor' => 'decimal:2'];

    public function conteo()
    {
        return $this->belongsTo(Conteo::class);
    }

    public function destino()
    {
        return $this->belongsTo(ConteoDestino::class, 'conteo_destino_id');
    }

    public function tercero()
    {
        return $this->belongsTo(ConteoTercero::class, 'conteo_tercero_id');
    }
}
