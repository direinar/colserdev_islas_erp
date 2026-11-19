<?php

namespace App\Models\Nomina;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entrega de dotación (formato de control de dotación): tres entregas al año.
 */
class Dotacion extends Model
{
    /**
     * Entregas de ley con su fecha límite (mes-día).
     *
     * @var array<int, array{0: string, 1: string}>
     */
    public const ENTREGAS = [
        1 => ['Primera entrega', '04-30'],
        2 => ['Segunda entrega', '08-31'],
        3 => ['Tercera entrega', '12-20'],
    ];

    protected $table = 'nomina_dotaciones';

    protected $fillable = [
        'empleado_id',
        'fecha_entrega',
        'anio',
        'entrega',
        'elementos',
        'observaciones',
    ];

    protected $casts = [
        'fecha_entrega' => 'date',
        'anio' => 'integer',
        'entrega' => 'integer',
    ];

    /**
     * @return BelongsTo<Empleado, $this>
     */
    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }
}
