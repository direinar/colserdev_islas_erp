<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TurnoSurtidor extends Model
{
    /**
     * Mangueras de la EDS en el orden de la planilla, con su combustible. Cada isla tiene cuatro.
     *
     * @var array<string, string>
     */
    public const MANGUERAS = [
        'PLUS 01' => 'corriente',
        'PLUS 02' => 'corriente',
        'ACPM 03' => 'acpm',
        'ACPM 04' => 'acpm',
        'PLUS 05' => 'corriente',
        'PLUS 06' => 'corriente',
        'ACPM 07' => 'acpm',
        'ACPM 08' => 'acpm',
        'PLUS 09' => 'corriente',
        'PLUS 10' => 'corriente',
        'ACPM 11' => 'acpm',
        'ACPM 12' => 'acpm',
    ];

    protected $table = 'turno_surtidores';

    protected $fillable = [
        'turno_id',
        'manguera',
        'combustible',
        'lectura_inicial',
        'lectura_final',
        'contador_retiro',
        'contador_instalacion',
        'galones',
        'total',
    ];

    protected $casts = [
        'lectura_inicial' => 'decimal:3',
        'lectura_final' => 'decimal:3',
        'contador_retiro' => 'decimal:3',
        'contador_instalacion' => 'decimal:3',
        'galones' => 'decimal:3',
        'total' => 'decimal:2',
    ];

    public function turno()
    {
        return $this->belongsTo(Turno::class);
    }

    /**
     * Galones vendidos por la manguera. Con cambio de contador en el turno se suma lo que
     * marcó el contador viejo hasta retirarlo y lo que marcó el nuevo desde que se instaló.
     * Sin lectura final (turno abierto) aún no hay venta.
     */
    public static function calcularGalones(float $inicial, ?float $final, ?float $retiro = null, ?float $instalacion = null): float
    {
        if ($final === null) {
            return 0.0;
        }

        if ($retiro !== null && $instalacion !== null) {
            return max(0, $retiro - $inicial) + max(0, $final - $instalacion);
        }

        return max(0, $final - $inicial);
    }

    public function tieneCambioContador(): bool
    {
        return $this->contador_retiro !== null && $this->contador_instalacion !== null;
    }

    /**
     * Lo que se suma a (final - inicial) por el cambio de contador; lo usa la pantalla para los GLS.
     */
    public function ajusteContador(): float
    {
        return $this->tieneCambioContador() ? (float) $this->contador_retiro - (float) $this->contador_instalacion : 0.0;
    }

    /**
     * Lectura mínima que puede tener la final: la inicial o, con cambio de contador, la del contador nuevo.
     */
    public function lecturaMinimaFinal(): float
    {
        return $this->tieneCambioContador() ? (float) $this->contador_instalacion : (float) $this->lectura_inicial;
    }
}
