<?php

namespace App\Services;

use App\Models\Conteo;
use App\Models\ConteoAplicacion;
use App\Models\ConteoCuenta;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Comprobante contable del conteo (plantilla "COMPROBANTE DE CONTABILIDAD DE CONTEO"):
 * un débito por cada línea de la consignación en bancos y otras aplicaciones, con la cuenta,
 * el tercero y el NIT de la tabla de cuentas y el concepto "… CON CONTEO No. X", y un crédito
 * fijo a caja por el total consignado.
 */
class ComprobanteConteoService
{
    /**
     * Cuenta de caja que se acredita por el total consignado ("FIJO POR TOTAL CONSIGNADO").
     */
    public const CUENTA_CAJA = '11050501';

    /**
     * @return array{conteo: Conteo, lineas: list<array{aplicacion_id: ?int, cuenta: ?string, concepto: string, tercero: ?string, nit: ?string, debito: float, credito: float, sin_cuenta: bool}>,
     *     debito: float, credito: float, total_consignado: float, sin_cuenta: int}
     */
    public function comprobante(Conteo $conteo): array
    {
        $aplicaciones = $conteo->aplicaciones()->with(['cuenta', 'destino', 'tercero'])->orderBy('id')->get()
            ->filter(fn (ConteoAplicacion $aplicacion): bool => round((float) $aplicacion->valor, 2) != 0.0);
        $lineas = [];

        foreach ($aplicaciones as $aplicacion) {
            $valor = (float) $aplicacion->valor;
            $cuenta = $aplicacion->cuenta;
            $lineas[] = [
                'aplicacion_id' => $aplicacion->id,
                'cuenta' => $cuenta?->cuenta,
                'concepto' => ($cuenta?->concepto ?? mb_strtoupper(trim(($aplicacion->destino?->nombre ?? 'SIN DESTINO').' '.($aplicacion->tercero?->nombre ?? '')))).' CON CONTEO No.'.$conteo->numero,
                'tercero' => $cuenta?->tercero,
                'nit' => $cuenta?->nit,
                'debito' => max($valor, 0.0),
                'credito' => max(-$valor, 0.0),
                'sin_cuenta' => $cuenta === null,
            ];
        }

        $total = (float) $aplicaciones->sum(fn (ConteoAplicacion $aplicacion): float => (float) $aplicacion->valor);

        if ($lineas !== []) {
            $lineas[] = [
                'aplicacion_id' => null,
                'cuenta' => self::CUENTA_CAJA,
                'concepto' => 'CONTEO No.'.$conteo->numero,
                'tercero' => null,
                'nit' => null,
                'debito' => max(-$total, 0.0),
                'credito' => max($total, 0.0),
                'sin_cuenta' => false,
            ];
        }

        return [
            'conteo' => $conteo,
            'lineas' => $lineas,
            'debito' => array_sum(array_column($lineas, 'debito')),
            'credito' => array_sum(array_column($lineas, 'credito')),
            'total_consignado' => $total,
            'sin_cuenta' => count(array_filter($lineas, fn (array $linea): bool => $linea['sin_cuenta'])),
        ];
    }

    /**
     * Comprobantes de los conteos con fecha en el periodo.
     *
     * @return array{comprobantes: list<array<string, mixed>>, debito: float, credito: float, sin_cuenta: int}
     */
    public function delPeriodo(string $desde, string $hasta): array
    {
        $comprobantes = Conteo::query()
            ->whereBetween('fecha', [$desde, $hasta])
            ->orderBy('numero')
            ->get()
            ->map(fn (Conteo $conteo): array => $this->comprobante($conteo))
            ->filter(fn (array $comprobante): bool => $comprobante['lineas'] !== [])
            ->values()
            ->all();

        return [
            'comprobantes' => $comprobantes,
            'debito' => array_sum(array_column($comprobantes, 'debito')),
            'credito' => array_sum(array_column($comprobantes, 'credito')),
            'sin_cuenta' => array_sum(array_column($comprobantes, 'sin_cuenta')),
        ];
    }

    /**
     * Cuenta sugerida para una línea: la última usada con el mismo destino y tercero, o con el mismo destino.
     */
    public function cuentaSugerida(?int $destinoId, ?int $terceroId, ?int $excluirConteoId = null): ?int
    {
        if ($destinoId === null) {
            return null;
        }

        $base = ConteoAplicacion::query()
            ->whereNotNull('conteo_cuenta_id')
            ->where('conteo_destino_id', $destinoId)
            ->when($excluirConteoId, fn ($query, int $id) => $query->where('conteo_id', '!=', $id))
            ->latest('id');

        return (clone $base)->where('conteo_tercero_id', $terceroId)->value('conteo_cuenta_id')
            ?? $base->value('conteo_cuenta_id');
    }

    /**
     * Asigna la cuenta contable de cada línea del conteo.
     *
     * @param  array<int|string, int|string|null>  $cuentas  aplicacion_id => conteo_cuenta_id
     */
    public function asignarCuentas(Conteo $conteo, array $cuentas): int
    {
        $asignadas = 0;

        DB::transaction(function () use ($conteo, $cuentas, &$asignadas): void {
            foreach ($conteo->aplicaciones()->get() as $aplicacion) {
                if (array_key_exists($aplicacion->id, $cuentas)) {
                    $aplicacion->update(['conteo_cuenta_id' => $cuentas[$aplicacion->id] ?: null]);
                    $asignadas += $cuentas[$aplicacion->id] ? 1 : 0;
                }
            }
        });

        return $asignadas;
    }

    /**
     * @return Collection<int, ConteoCuenta>
     */
    public function cuentas(): Collection
    {
        return ConteoCuenta::query()->orderBy('cuenta')->orderBy('concepto')->get();
    }
}
