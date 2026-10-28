<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tabla de conceptos (destinos) de la consignación en bancos y otras aplicaciones.
 */
class ConteoDestino extends Model
{
    protected $fillable = ['nombre'];

    public function aplicaciones()
    {
        return $this->hasMany(ConteoAplicacion::class, 'conteo_destino_id');
    }
}
