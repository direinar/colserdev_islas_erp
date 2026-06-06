<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Turno extends Model
{
    protected $fillable = ['fecha', 'numero_turno', 'nombre_vendedor', 'revisado_por'];

    protected $casts = ['fecha' => 'date'];

    public function ventasSurtidor(): HasMany
    {
        return $this->hasMany(VentaSurtidor::class);
    }

    public function lecturasSurtidor(): HasMany
    {
        return $this->hasMany(LecturaSurtidor::class);
    }

    public function consignaciones(): HasMany
    {
        return $this->hasMany(Consignacion::class);
    }

    public function carteraCredito(): HasMany
    {
        return $this->hasMany(CarteraCredito::class);
    }

    public function mediosPagoElectronicos(): HasOne
    {
        return $this->hasOne(MediosPagoElectronico::class);
    }

    public function ureaLubricantes(): HasMany
    {
        return $this->hasMany(VentaUreaLubricante::class);
    }

    public function recaudosAnticipo(): HasMany
    {
        return $this->hasMany(RecaudoAnticipo::class);
    }

    public function varios(): HasMany
    {
        return $this->hasMany(Vario::class);
    }

    public function recaudosAdmin(): HasMany
    {
        return $this->hasMany(RecaudoAdministracion::class);
    }
}
