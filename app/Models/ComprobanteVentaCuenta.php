<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComprobanteVentaCuenta extends Model
{
    protected $table = 'comprobante_ventas_cuentas';

    protected $fillable = [
        'clave',
        'descripcion',
        'cuenta',
    ];
}
