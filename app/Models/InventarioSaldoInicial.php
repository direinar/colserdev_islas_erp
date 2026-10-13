<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Saldo con el que arranca un kardex de inventario a su fecha de corte.
 */
class InventarioSaldoInicial extends Model
{
    public const GASOLINA = 'gasolina';

    public const ACPM = 'acpm';

    public const CANASTILLA = 'canastilla';

    protected $table = 'inventario_saldos_iniciales';

    protected $fillable = [
        'inventario',
        'referencia',
        'fecha_corte',
        'unidades',
        'costo_unitario',
    ];

    protected $casts = [
        'fecha_corte' => 'date',
        'unidades' => 'decimal:3',
        'costo_unitario' => 'decimal:2',
    ];
}
