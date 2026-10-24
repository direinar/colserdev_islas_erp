<?php

namespace App\Services;

use App\Models\FuelPrice;
use App\Models\LecturaElectronica;
use App\Models\Turno;
use App\Models\TurnoSurtidor;
use App\Models\User;
use App\Support\NumberParser;
use Illuminate\Support\Facades\DB;

/**
 * Lectura electrónica de los surtidores por fecha (antes estaba en cada planilla de turno).
 *
 * (A) Venta según lecturas: galones de cada manguera (final - inicial) por el precio del día.
 * (B) Venta según cierres de IAPROPIADA del día: suma de los turnos de la fecha, igual que
 *     el control de tanques.
 * (C) Sobrante o faltante por lectura de surtidores = A - B, en galones y en valor.
 *
 * La lectura inicial de cada manguera es la final del día anterior registrado; si aún no hay
 * días registrados, la última lectura que quedó en las planillas de turno.
 */
class LecturaElectronicaService
{
    /**
     * @return array{
     *     fecha: string,
     *     filas: list<array{manguera: string, combustible: string, inicial: float|null, final: float|null, galones: float, inicial_bloqueada: bool}>,
     *     precios: array{corriente: float, acpm: float},
     *     lecturas: array<string, array{galones: float, valor: float}>,
     *     iapropiada: array<string, array{galones: float, valor: float}>,
     *     diferencia: array<string, array{galones: float, valor: float}>
     * }
     */
    public function informe(string $fecha): array
    {
        $guardadas = LecturaElectronica::query()->whereDate('fecha', $fecha)->get()->keyBy('manguera');
        $filas = [];

        foreach (LecturaElectronica::MANGUERAS as $manguera => $combustible) {
            $guardada = $guardadas->get($manguera);
            $anterior = $this->lecturaAnterior($manguera, $fecha);
            $inicial = $guardada ? (float) $guardada->lectura_inicial : $anterior;
            $final = $guardada?->lectura_final === null ? null : (float) $guardada->lectura_final;

            $filas[] = [
                'manguera' => $manguera,
                'combustible' => $combustible,
                'inicial' => $inicial,
                'final' => $final,
                'galones' => $guardada ? (float) $guardada->galones : 0.0,
                'inicial_bloqueada' => $anterior !== null,
            ];
        }

        $precios = $this->precios($fecha);
        $lecturas = $this->porCombustible(
            collect($filas)->where('combustible', 'corriente')->sum('galones'),
            collect($filas)->where('combustible', 'acpm')->sum('galones'),
            $precios,
        );

        $turnos = Turno::query()->whereDate('fecha', $fecha)->get();
        $iapropiada = [
            'corriente' => ['galones' => (float) $turnos->sum('tirillas_galones_corriente'), 'valor' => (float) $turnos->sum('tirillas_valor_corriente')],
            'acpm' => ['galones' => (float) $turnos->sum('tirillas_galones_acpm'), 'valor' => (float) $turnos->sum('tirillas_valor_acpm')],
        ];
        $iapropiada['total'] = $this->suma($iapropiada['corriente'], $iapropiada['acpm']);

        $diferencia = [];
        foreach (['corriente', 'acpm', 'total'] as $clave) {
            $diferencia[$clave] = [
                'galones' => $lecturas[$clave]['galones'] - $iapropiada[$clave]['galones'],
                'valor' => $lecturas[$clave]['valor'] - $iapropiada[$clave]['valor'],
            ];
        }

        return [
            'fecha' => $fecha,
            'filas' => $filas,
            'precios' => $precios,
            'lecturas' => $lecturas,
            'iapropiada' => $iapropiada,
            'diferencia' => $diferencia,
        ];
    }

    /**
     * Guarda las lecturas del día. La inicial no se recibe si viene del día anterior; si un
     * día posterior ya está registrado, su inicial pasa a ser la nueva final de este día.
     *
     * @param  array<string, mixed>  $iniciales
     * @param  array<string, mixed>  $finales
     */
    public function guardar(string $fecha, array $iniciales, array $finales, User $usuario): void
    {
        DB::transaction(function () use ($fecha, $iniciales, $finales, $usuario): void {
            foreach (LecturaElectronica::MANGUERAS as $manguera => $combustible) {
                // 'fecha' se guarda con hora: se busca con whereDate, no con igualdad exacta.
                $fila = LecturaElectronica::query()->whereDate('fecha', $fecha)->where('manguera', $manguera)->first()
                    ?? new LecturaElectronica(['fecha' => $fecha, 'manguera' => $manguera]);

                $anterior = $this->lecturaAnterior($manguera, $fecha);
                $inicial = $anterior
                    ?? $this->numero($iniciales[$manguera] ?? null)
                    ?? ($fila->exists ? (float) $fila->lectura_inicial : 0.0);
                $final = $this->numero($finales[$manguera] ?? null);

                $fila->fill([
                    'combustible' => $combustible,
                    'lectura_inicial' => $inicial,
                    'lectura_final' => $final,
                    'galones' => $this->galones($inicial, $final),
                    'user_id' => $usuario->id,
                ])->save();

                if ($final === null) {
                    continue;
                }

                $siguiente = LecturaElectronica::query()
                    ->where('manguera', $manguera)
                    ->whereDate('fecha', '>', $fecha)
                    ->orderBy('fecha')
                    ->first();

                $siguiente?->update([
                    'lectura_inicial' => $final,
                    'galones' => $this->galones($final, $siguiente->lectura_final === null ? null : (float) $siguiente->lectura_final),
                ]);
            }
        });
    }

    /**
     * Final del último día registrado antes de la fecha; si no hay, la última lectura que
     * quedó en las planillas de turno anteriores a esa fecha.
     */
    private function lecturaAnterior(string $manguera, string $fecha): ?float
    {
        $diaAnterior = LecturaElectronica::query()
            ->where('manguera', $manguera)
            ->whereDate('fecha', '<', $fecha)
            ->whereNotNull('lectura_final')
            ->orderByDesc('fecha')
            ->value('lectura_final');

        if ($diaAnterior !== null) {
            return (float) $diaAnterior;
        }

        $deTurno = TurnoSurtidor::query()
            ->join('turnos', 'turnos.id', '=', 'turno_surtidores.turno_id')
            ->where('turno_surtidores.manguera', $manguera)
            ->whereDate('turnos.fecha', '<', $fecha)
            ->orderByDesc('turnos.numero_turno')
            ->value('turno_surtidores.lectura_final');

        return $deTurno !== null ? (float) $deTurno : null;
    }

    /**
     * Precio del último turno de la fecha; sin turnos, el que rige para el próximo turno.
     *
     * @return array{corriente: float, acpm: float}
     */
    private function precios(string $fecha): array
    {
        $ultimoTurno = Turno::query()->whereDate('fecha', $fecha)->orderByDesc('numero_turno')->first();
        $siguiente = (int) Turno::query()->max('numero_turno') + 1;

        return [
            'corriente' => (float) ($ultimoTurno?->precio_corriente ?: FuelPrice::precioParaTurno('Gasolina', $siguiente)),
            'acpm' => (float) ($ultimoTurno?->precio_acpm ?: FuelPrice::precioParaTurno('ACPM', $siguiente)),
        ];
    }

    /**
     * @param  array{corriente: float, acpm: float}  $precios
     * @return array<string, array{galones: float, valor: float}>
     */
    private function porCombustible(float $galonesCorriente, float $galonesAcpm, array $precios): array
    {
        $corriente = ['galones' => $galonesCorriente, 'valor' => round($galonesCorriente * $precios['corriente'])];
        $acpm = ['galones' => $galonesAcpm, 'valor' => round($galonesAcpm * $precios['acpm'])];

        return ['corriente' => $corriente, 'acpm' => $acpm, 'total' => $this->suma($corriente, $acpm)];
    }

    /**
     * @param  array{galones: float, valor: float}  $a
     * @param  array{galones: float, valor: float}  $b
     * @return array{galones: float, valor: float}
     */
    private function suma(array $a, array $b): array
    {
        return ['galones' => $a['galones'] + $b['galones'], 'valor' => $a['valor'] + $b['valor']];
    }

    private function galones(float $inicial, ?float $final): float
    {
        return $final === null ? 0.0 : max(0, $final - $inicial);
    }

    private function numero(mixed $valor): ?float
    {
        $texto = trim((string) ($valor ?? ''));

        return $texto === '' ? null : NumberParser::quantity($texto);
    }
}
