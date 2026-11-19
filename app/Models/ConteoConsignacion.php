<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Consignación interna del conteo: el valor se toma de la planilla de turno que tiene ese No.
 */
class ConteoConsignacion extends Model
{
    protected $table = 'conteo_consignaciones';

    /**
     * manual: fila digitada a mano (No. opcional, valor y concepto), fuera del consecutivo.
     */
    protected $fillable = ['conteo_id', 'numero', 'valor', 'turno_id', 'manual', 'concepto'];

    protected $casts = ['valor' => 'decimal:2', 'numero' => 'integer', 'manual' => 'boolean'];

    public function conteo()
    {
        return $this->belongsTo(Conteo::class);
    }

    public function turno()
    {
        return $this->belongsTo(Turno::class);
    }
}
