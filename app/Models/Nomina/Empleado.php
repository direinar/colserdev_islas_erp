<?php

namespace App\Models\Nomina;

use Database\Factories\Nomina\EmpleadoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empleado extends Model
{
    /** @use HasFactory<EmpleadoFactory> */
    use HasFactory;

    protected $table = 'nomina_empleados';

    protected $fillable = [
        'tipo_documento',
        'numero_documento',
        'nombres',
        'apellidos',
        'nombre_corto',
        'telefono',
        'correo',
        'direccion',
        'cargo',
        'fecha_ingreso',
        'tipo_contrato',
        'fecha_vencimiento_contrato',
        'salario_basico',
        'recibe_auxilio_transporte',
        'aplica_recargos',
        'eps',
        'fondo_pension',
        'fondo_cesantias',
        'forma_pago',
        'banco',
        'tipo_cuenta',
        'numero_cuenta',
        'activo',
        'fecha_retiro',
        'motivo_retiro',
    ];

    protected $casts = [
        'fecha_ingreso' => 'date',
        'fecha_vencimiento_contrato' => 'date',
        'fecha_retiro' => 'date',
        'salario_basico' => 'decimal:2',
        'recibe_auxilio_transporte' => 'boolean',
        'aplica_recargos' => 'boolean',
        'activo' => 'boolean',
    ];

    /**
     * @return array<string, string>
     */
    public static function tiposDocumento(): array
    {
        return [
            'CC' => 'Cédula de ciudadanía',
            'CE' => 'Cédula de extranjería',
            'PPT' => 'Permiso por protección temporal',
            'PA' => 'Pasaporte',
            'TI' => 'Tarjeta de identidad',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function tiposContrato(): array
    {
        return [
            'indefinido' => 'Término indefinido',
            'fijo' => 'Término fijo',
            'obra_labor' => 'Obra o labor',
            'aprendizaje' => 'Aprendizaje',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function formasPago(): array
    {
        return [
            'transferencia' => 'Transferencia',
            'efectivo' => 'Efectivo',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function tiposCuenta(): array
    {
        return [
            'ahorros' => 'Ahorros',
            'corriente' => 'Corriente',
        ];
    }

    public function asignacionesTurno(): HasMany
    {
        return $this->hasMany(AsignacionTurno::class);
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim($this->nombres.' '.$this->apellidos);
    }

    /**
     * Contrato con fecha de vencimiento dentro de los próximos días indicados (o ya vencido).
     */
    public function contratoPorVencer(int $dias = 30): bool
    {
        return $this->activo
            && $this->fecha_vencimiento_contrato !== null
            && $this->fecha_vencimiento_contrato->lte(now()->addDays($dias));
    }
}
