<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Turno extends Model
{
    protected $fillable = [
        'fecha',
        'numero_turno',
        'nombre_vendedor',
        'revisado_por',          // nombre del administrador que revisó la planilla
        'revisado',
        'revisado_at',
        'precio_corriente',
        'precio_acpm',
        'tirillas_galones_corriente',
        'tirillas_galones_acpm',
        'tirillas_valor_corriente',
        'tirillas_valor_acpm',
        'total_ventas',
        'lecturas_galones_corriente',
        'lecturas_galones_acpm',
        'lecturas_valor_corriente',
        'lecturas_valor_acpm',
        'total_venta_lecturas',
        'traslado_sobrante',
        'traslado_faltante',
        'sobrante_pendiente',
        'observaciones',
        'user_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'revisado' => 'boolean',
        'revisado_at' => 'datetime',
        'precio_corriente' => 'decimal:2',
        'precio_acpm' => 'decimal:2',
        'tirillas_galones_corriente' => 'decimal:3',
        'tirillas_galones_acpm' => 'decimal:3',
        'tirillas_valor_corriente' => 'decimal:2',
        'tirillas_valor_acpm' => 'decimal:2',
        'total_ventas' => 'decimal:2',
        'lecturas_galones_corriente' => 'decimal:3',
        'lecturas_galones_acpm' => 'decimal:3',
        'lecturas_valor_corriente' => 'decimal:2',
        'lecturas_valor_acpm' => 'decimal:2',
        'total_venta_lecturas' => 'decimal:2',
        'traslado_sobrante' => 'decimal:2',
        'traslado_faltante' => 'decimal:2',
        'sobrante_pendiente' => 'decimal:2',
    ];

    /**
     * Quién creó la planilla.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Una planilla revisada no la modifica nadie. El islero y el jefe de patios solo
     * modifican las planillas que ellos crearon; el administrador, cualquiera.
     */
    public function editablePor(User $user): bool
    {
        if ($this->revisado) {
            return false;
        }

        return $user->isAdministrador() || ! $this->exists || $this->user_id === $user->id;
    }

    /**
     * El islero solo ve sus planillas; el jefe de patios, las suyas y las de los isleros;
     * el administrador, todas.
     */
    public function visiblePara(User $user): bool
    {
        if ($user->isAdministrador() || ! $this->exists || $this->user_id === $user->id) {
            return true;
        }

        return $user->isJefePatios() && (bool) $this->user?->isIslero();
    }

    /**
     * Misma regla que visiblePara(), para listados.
     *
     * @param  Builder<Turno>  $query
     */
    public function scopeVisiblePara(Builder $query, User $user): void
    {
        if ($user->isAdministrador()) {
            return;
        }

        $query->where(function (Builder $visibles) use ($user) {
            $visibles->where('user_id', $user->id);

            if ($user->isJefePatios()) {
                $visibles->orWhereHas('user', fn (Builder $creador) => $creador->where('role', User::ROLE_ISLERO));
            }
        });
    }

    // Relaciones — todas correctas, solo agregar las que faltaban
    public function ventas()
    {
        return $this->hasMany(TurnoVenta::class);
    }

    public function surtidores()
    {
        return $this->hasMany(TurnoSurtidor::class);
    }

    public function lubricantes()
    {
        return $this->hasMany(TurnoLubricante::class);
    }

    public function mediosPago()
    {
        return $this->hasMany(TurnoMedioPago::class);
    }

    public function consignaciones()
    {
        return $this->hasMany(TurnoConsignacion::class);
    }

    public function descuentos()
    {
        return $this->hasMany(TurnoDescuento::class);
    }

    public function cartera()
    {
        return $this->hasMany(TurnoCartera::class);
    }

    public function qrPagos()
    {
        return $this->hasMany(TurnoQrPago::class);
    }

    public function recaudos()
    {
        return $this->hasMany(TurnoRecaudo::class);
    }

    public function transferencias()
    {
        return $this->hasMany(TurnoTransferencia::class);
    }

    public function gasolinaEds()
    {
        return $this->hasMany(TurnoGasolinaEds::class);
    }

    public function varios()
    {
        return $this->hasMany(TurnoVarios::class);
    }

    public function recaudosAdmin()
    {
        return $this->hasMany(TurnoRecaudoAdmin::class);
    }
}
