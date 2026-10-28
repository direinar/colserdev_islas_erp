<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Conteo del efectivo de la EDS (plantilla "PROGRAMACION CONTEO"). Lo registran el
 * administrador o el gerente cuando lo deciden; no lleva revisión: al guardarlo queda en firme.
 * Los conteos se encadenan (último No. de consignación y saldo de moneda), por eso solo el
 * último se puede modificar.
 */
class Conteo extends Model
{
    /**
     * Denominaciones del cuadre de efectivo; FAJOS es un solo valor (billetes agrupados).
     *
     * @var list<string>
     */
    public const DENOMINACIONES = ['100 MIL', '50 MIL', '20 MIL', '10 MIL', '5 MIL', 'DOS MIL', 'UN MIL', 'FAJOS'];

    /**
     * Submódulos: una tabla de la plantilla cada uno.
     *
     * @var array<string, string>
     */
    public const SECCIONES = [
        'efectivo' => 'Cuadre de efectivo',
        'retiros' => 'Retiros antes del cierre',
        'consignaciones' => 'Consignaciones internas',
        'gastos' => 'Cuadre del conteo',
        'aplicaciones' => 'Consignación en bancos',
        'moneda' => 'Control de moneda',
    ];

    protected $fillable = [
        'numero',
        'fecha',
        'moneda',
        'consignacion_anterior',
        'saldo_moneda_inicial',
        'ultima_consignacion',
        'user_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'moneda' => 'decimal:2',
        'saldo_moneda_inicial' => 'decimal:2',
        'numero' => 'integer',
        'consignacion_anterior' => 'integer',
        'ultima_consignacion' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function billetes()
    {
        return $this->hasMany(ConteoBillete::class);
    }

    public function movimientos()
    {
        return $this->hasMany(ConteoMovimiento::class);
    }

    public function consignaciones()
    {
        return $this->hasMany(ConteoConsignacion::class)->orderBy('numero');
    }

    public function aplicaciones()
    {
        return $this->hasMany(ConteoAplicacion::class);
    }

    public function anterior(): ?self
    {
        return static::query()->where('numero', '<', $this->numero)->orderByDesc('numero')->first();
    }

    public function esPrimero(): bool
    {
        return ! static::query()->where('numero', '<', $this->numero)->exists();
    }

    /**
     * Solo el último conteo se modifica: el siguiente arrastra sus valores.
     */
    public function esUltimo(): bool
    {
        return ! static::query()->where('numero', '>', $this->numero)->exists();
    }
}
