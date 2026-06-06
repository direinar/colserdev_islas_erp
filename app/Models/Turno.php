<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Turno extends Model
{
    protected $fillable = [
        'fecha',
        'numero_turno',
        'nombre_vendedor',
        'revisado_por',
        'precio_corriente',
        'precio_acpm',
        'ventas_surtidor',
        'lecturas',
        'consignaciones',
        'cartera',
        'tc_datafono_1',
        'tc_datafono_2',
        'tc_datafono_3',
        'transferencias_bancolombia',
        'gasolina_eds',
        'puntos_redimidos',
        'urea_lubricantes',
        'recaudos_anticipos',
        'varios',
        'recaudos_administracion',
        'traslado_sobrante',
        'traslado_faltante',
        'total_venta_iapropiada',
        'total_venta_lectura',
        'total_urea_sin_iva',
        'total_urea_iva',
        'subtotal_ingresos',
        'total_recaudos',
        'total_recibido',
        'faltante_sobrante_iapropiada',
        'faltante_sobrante_lectura',
    ];

    protected $casts = [
        'fecha' => 'date',
        'numero_turno' => 'integer',
        'precio_corriente' => 'decimal:2',
        'precio_acpm' => 'decimal:2',
        'ventas_surtidor' => 'array',
        'lecturas' => 'array',
        'consignaciones' => 'array',
        'cartera' => 'array',
        'tc_datafono_1' => 'decimal:2',
        'tc_datafono_2' => 'decimal:2',
        'tc_datafono_3' => 'decimal:2',
        'transferencias_bancolombia' => 'decimal:2',
        'gasolina_eds' => 'decimal:2',
        'puntos_redimidos' => 'decimal:2',
        'urea_lubricantes' => 'array',
        'recaudos_anticipos' => 'array',
        'varios' => 'array',
        'recaudos_administracion' => 'array',
        'traslado_sobrante' => 'decimal:2',
        'traslado_faltante' => 'decimal:2',
        'total_venta_iapropiada' => 'decimal:2',
        'total_venta_lectura' => 'decimal:2',
        'total_urea_sin_iva' => 'decimal:2',
        'total_urea_iva' => 'decimal:2',
        'subtotal_ingresos' => 'decimal:2',
        'total_recaudos' => 'decimal:2',
        'total_recibido' => 'decimal:2',
        'faltante_sobrante_iapropiada' => 'decimal:2',
        'faltante_sobrante_lectura' => 'decimal:2',
    ];
}
