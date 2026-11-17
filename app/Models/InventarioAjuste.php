<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ajuste de inventario (avería, pérdida, vencimiento, consumo interno o sobrante de un conteo).
 * Las salidas restan y las entradas suman en el kardex, al costo promedio del momento.
 */
class InventarioAjuste extends Model
{
    /**
     * Motivo => [nombre, entrada (true) o salida (false)].
     *
     * @var array<string, array{0: string, 1: bool}>
     */
    public const MOTIVOS = [
        'averia' => ['AVERÍA', false],
        'perdida' => ['PÉRDIDA O ROBO', false],
        'vencimiento' => ['VENCIMIENTO', false],
        'consumo_interno' => ['CONSUMO INTERNO', false],
        'sobrante' => ['SOBRANTE EN CONTEO', true],
    ];

    protected $fillable = ['fecha', 'inventario', 'referencia', 'motivo', 'unidades', 'observacion', 'user_id'];

    protected $casts = [
        'fecha' => 'date',
        'unidades' => 'decimal:3',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function esEntrada(): bool
    {
        return self::MOTIVOS[$this->motivo][1] ?? false;
    }

    public function nombreMotivo(): string
    {
        return self::MOTIVOS[$this->motivo][0] ?? mb_strtoupper($this->motivo);
    }
}
