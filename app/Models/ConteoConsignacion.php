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
     * referencia: No. alfanumérico de la fila manual tal como se digitó (numero queda solo si son dígitos).
     */
    protected $fillable = ['conteo_id', 'numero', 'referencia', 'valor', 'turno_id', 'manual', 'concepto'];

    protected $casts = ['valor' => 'decimal:2', 'numero' => 'integer', 'manual' => 'boolean'];

    public function conteo()
    {
        return $this->belongsTo(Conteo::class);
    }

    /**
     * No. que se muestra: el digitado en las filas manuales, o el del consecutivo.
     */
    public function numeroVisible(): string
    {
        return $this->manual ? (string) ($this->referencia ?? $this->numero) : (string) $this->numero;
    }

    public function turno()
    {
        return $this->belongsTo(Turno::class);
    }
}
