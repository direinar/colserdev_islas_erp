<?php

namespace App\Models\Nomina;

use Carbon\CarbonImmutable;
use Database\Factories\Nomina\LiquidacionNominaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Nómina del mes (hoja NOMINA de la plantilla): un registro por mes con una fila por empleado.
 * En BORRADOR se digitan las novedades; APROBADA queda fija y alimenta los acumulados.
 */
class LiquidacionNomina extends Model
{
    /** @use HasFactory<LiquidacionNominaFactory> */
    use HasFactory;

    public const ESTADO_BORRADOR = 'borrador';

    public const ESTADO_APROBADA = 'aprobada';

    protected $table = 'nomina_liquidaciones';

    protected $fillable = [
        'anio',
        'mes',
        'parametro_liquidacion_id',
        'estado',
        'observaciones',
        'aprobada_por',
        'aprobada_at',
    ];

    protected $casts = [
        'anio' => 'integer',
        'mes' => 'integer',
        'aprobada_at' => 'datetime',
    ];

    /**
     * @return HasMany<LiquidacionNominaEmpleado, $this>
     */
    public function empleados(): HasMany
    {
        return $this->hasMany(LiquidacionNominaEmpleado::class, 'liquidacion_id');
    }

    /**
     * @return BelongsTo<ParametroLiquidacion, $this>
     */
    public function parametros(): BelongsTo
    {
        return $this->belongsTo(ParametroLiquidacion::class, 'parametro_liquidacion_id');
    }

    public function inicioDelMes(): CarbonImmutable
    {
        return CarbonImmutable::create($this->anio, $this->mes, 1);
    }

    public function finDelMes(): CarbonImmutable
    {
        return $this->inicioDelMes()->endOfMonth()->startOfDay();
    }

    public function nombreDelMes(): string
    {
        return mb_strtoupper($this->inicioDelMes()->locale('es')->translatedFormat('F Y'));
    }

    public function estaAprobada(): bool
    {
        return $this->estado === self::ESTADO_APROBADA;
    }

    public function planillaTurnos(): ?PlanillaTurno
    {
        return PlanillaTurno::query()->where('anio', $this->anio)->where('mes', $this->mes)->first();
    }
}
