<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tabla de cuentas, conceptos, terceros y NIT del comprobante contable del conteo.
 */
class ConteoCuenta extends Model
{
    protected $table = 'conteo_cuentas';

    protected $fillable = ['cuenta', 'concepto', 'tercero', 'nit'];

    public function aplicaciones()
    {
        return $this->hasMany(ConteoAplicacion::class, 'conteo_cuenta_id');
    }

    public function etiqueta(): string
    {
        return $this->cuenta.' — '.$this->concepto.($this->tercero ? ' ('.$this->tercero.')' : '');
    }
}
