<?php

namespace App\Models\Nomina;

use Illuminate\Database\Eloquent\Model;

/**
 * Cuenta contable de cada concepto de la nómina (hoja CONTABILIZACION).
 */
class CuentaContableNomina extends Model
{
    public const NATURALEZA_DEBITO = 'debito';

    public const NATURALEZA_CREDITO = 'credito';

    protected $table = 'nomina_cuentas_contables';

    protected $fillable = [
        'concepto',
        'nombre',
        'cuenta',
        'naturaleza',
        'orden',
    ];

    protected $casts = [
        'orden' => 'integer',
    ];
}
