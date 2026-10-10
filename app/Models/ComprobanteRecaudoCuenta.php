<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComprobanteRecaudoCuenta extends Model
{
    public const RECAUDO_ISLAS = 'recaudo_islas';

    public const CARTERA_CLIENTES = 'cartera_clientes';

    protected $table = 'comprobante_recaudos_cuentas';

    protected $fillable = [
        'clave',
        'descripcion',
        'cuenta',
    ];
}
