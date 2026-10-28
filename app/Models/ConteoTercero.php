<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tabla de bancos y terceros de la consignación en bancos y otras aplicaciones.
 */
class ConteoTercero extends Model
{
    public const TIPOS = ['banco' => 'Banco', 'tercero' => 'Tercero'];

    protected $fillable = ['nombre', 'tipo'];

    public function aplicaciones()
    {
        return $this->hasMany(ConteoAplicacion::class, 'conteo_tercero_id');
    }
}
