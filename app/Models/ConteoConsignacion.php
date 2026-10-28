<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Consignación interna del conteo: el valor se toma de la planilla de turno que tiene ese No.
 */
class ConteoConsignacion extends Model
{
    protected $table = 'conteo_consignaciones';

    protected $fillable = ['conteo_id', 'numero', 'valor', 'turno_id'];

    protected $casts = ['valor' => 'decimal:2', 'numero' => 'integer'];

    public function conteo()
    {
        return $this->belongsTo(Conteo::class);
    }

    public function turno()
    {
        return $this->belongsTo(Turno::class);
    }
}
