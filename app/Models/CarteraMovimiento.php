<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarteraMovimiento extends Model
{
    /** Venta a crédito de la planilla de turno (CARTERA - CRÉDITO DIRECTO): cargo. */
    public const ORIGEN_CREDITO_DIRECTO = 'credito_directo';

    /** Planilla de turno: RECAUDOS Y ANTICIPOS POR ISLAS. Abono. */
    public const ORIGEN_RECAUDO_ISLAS = 'recaudo_islas';

    /** Planilla de turno: RECAUDOS POR ADMINISTRACIÓN. Abono. */
    public const ORIGEN_RECAUDO_ADMIN = 'recaudo_admin';

    /** Saldo del cliente al arrancar el sistema (se carga una sola vez). */
    public const ORIGEN_SALDO_INICIAL = 'saldo_inicial';

    protected $table = 'cartera_movimientos';

    protected $fillable = [
        'customer_id',
        'turno_id',
        'origen',
        'saldo_inicial',
        'fecha_inicial',
        'fecha_final',
        'planillas',
        'fecha',
        'factura',
        'placas',
        'producto',
        'galones',
        'vr_unitario',
        'bruto',
        'descuento',
        'vr_neto_cargo',
        'abonos',
        'saldo',
        'cuenta',
        'concepto',
        'tercero',
        'nit',
        'debito',
        'credito',
    ];

    protected $casts = [
        'saldo_inicial' => 'decimal:0',
        'fecha_inicial' => 'date',
        'fecha_final' => 'date',
        'fecha' => 'date',
        'galones' => 'decimal:3',
        'vr_unitario' => 'decimal:0',
        'bruto' => 'decimal:0',
        'descuento' => 'decimal:0',
        'vr_neto_cargo' => 'decimal:0',
        'abonos' => 'decimal:0',
        'saldo' => 'decimal:0',
        'debito' => 'decimal:0',
        'credito' => 'decimal:0',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Planilla de turno que originó el movimiento (CARTERA - CRÉDITO DIRECTO).
     * Nulo cuando el movimiento se registró manualmente en el módulo de cartera.
     */
    public function turno()
    {
        return $this->belongsTo(Turno::class);
    }

    public function esDeTurno(): bool
    {
        return $this->turno_id !== null;
    }

    /**
     * Solo las ventas a crédito llevan datos del vehículo (placas, producto,
     * galones, vr. unitario, descuento) para el estado de cuenta del cliente.
     */
    public function llevaDatosVehiculo(): bool
    {
        return $this->origen === self::ORIGEN_CREDITO_DIRECTO;
    }

    public function esSaldoInicial(): bool
    {
        return $this->origen === self::ORIGEN_SALDO_INICIAL;
    }
}
