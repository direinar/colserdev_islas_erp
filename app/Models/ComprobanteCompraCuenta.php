<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComprobanteCompraCuenta extends Model
{
    public const INVENTARIO_COMBUSTIBLES = 'inventario_combustibles';

    public const PROVEEDOR_COMBUSTIBLES = 'proveedor_combustibles';

    public const INVENTARIO_CANASTILLA = 'inventario_canastilla';

    public const IVA_CANASTILLA = 'iva_canastilla';

    public const PROVEEDOR_CANASTILLA = 'proveedor_canastilla';

    public const DESCUENTOS_COMPRAS = 'descuentos_compras';

    protected $table = 'comprobante_compras_cuentas';

    protected $fillable = [
        'clave',
        'descripcion',
        'cuenta',
        'proveedor_id',
    ];

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }
}
