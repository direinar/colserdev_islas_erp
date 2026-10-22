<?php

namespace App\Services;

use App\Models\FuelPrice;
use App\Models\Turno;
use App\Models\TurnoSurtidor;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Apertura y cierre de la planilla por mangueras. Los isleros nunca comparten mangueras:
 * al abrir el turno cada uno toma las suyas, y una manguera solo puede estar en una planilla
 * abierta. La lectura inicial es la última lectura final CERRADA de la manguera, nunca la de
 * un turno que sigue abierto. Al cerrar (todas las lecturas finales, ninguna menor que la
 * inicial) las mangueras quedan libres para el turno siguiente.
 */
class ManguerasTurnoService
{
    /**
     * Mangueras tomadas por una planilla abierta, con su turno y quién lo abrió.
     *
     * @return Collection<string, TurnoSurtidor>
     */
    public function ocupadas(): Collection
    {
        return TurnoSurtidor::query()
            ->with('turno.user')
            ->whereHas('turno', fn ($turno) => $turno->where('estado', Turno::ESTADO_ABIERTA))
            ->get()
            // Colección base: en la de Eloquent, only() filtra por id del modelo y no por manguera.
            ->toBase()
            ->keyBy('manguera');
    }

    /**
     * Última lectura final cerrada de cada manguera, opcionalmente de los turnos anteriores a uno.
     *
     * @return array<string, float>
     */
    public function ultimasLecturas(?int $antesDelTurno = null): array
    {
        $lecturas = [];

        foreach (array_keys(TurnoSurtidor::MANGUERAS) as $manguera) {
            $lectura = TurnoSurtidor::query()
                ->join('turnos', 'turnos.id', '=', 'turno_surtidores.turno_id')
                ->where('turno_surtidores.manguera', $manguera)
                ->whereNotNull('turno_surtidores.lectura_final')
                ->where('turnos.estado', Turno::ESTADO_CERRADA)
                ->when($antesDelTurno, fn ($query) => $query->where('turnos.numero_turno', '<', $antesDelTurno))
                ->orderByDesc('turnos.numero_turno')
                ->value('turno_surtidores.lectura_final');

            if ($lectura !== null) {
                $lecturas[$manguera] = (float) $lectura;
            }
        }

        return $lecturas;
    }

    /**
     * Mangueras de la planilla que un turno posterior ya tomó (su lectura final ya es la
     * inicial de ese turno), con el número de ese turno.
     *
     * @return array<string, int>
     */
    public function tomadasDespues(Turno $turno): array
    {
        return TurnoSurtidor::query()
            ->join('turnos', 'turnos.id', '=', 'turno_surtidores.turno_id')
            ->where('turnos.numero_turno', '>', $turno->numero_turno)
            ->orderBy('turnos.numero_turno')
            ->get(['turno_surtidores.manguera', 'turnos.numero_turno'])
            ->unique('manguera')
            ->mapWithKeys(fn (TurnoSurtidor $fila): array => [$fila->manguera => (int) $fila->numero_turno])
            ->all();
    }

    public function turnoDelDiaSugerido(string $fecha): int
    {
        return (int) Turno::query()->whereDate('fecha', $fecha)->max('turno_dia') + 1;
    }

    /**
     * Abre la planilla con las mangueras elegidas y sus lecturas iniciales.
     *
     * @param  array{fecha: string, turno_dia: int|string, hora_inicio: string, mangueras: list<string>}  $datos
     */
    public function abrir(array $datos, User $usuario): Turno
    {
        return DB::transaction(function () use ($datos, $usuario): Turno {
            // Bloquea las planillas abiertas: dos isleros no pueden tomar la misma manguera a la vez.
            Turno::query()->where('estado', Turno::ESTADO_ABIERTA)->lockForUpdate()->get();

            $ocupadas = $this->ocupadas()->only($datos['mangueras']);

            if ($ocupadas->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'mangueras' => $ocupadas->map(fn (TurnoSurtidor $fila): string => $fila->manguera.' está en el turno #'
                        .$fila->turno->numero_turno.' de '.($fila->turno->user?->name ?? $fila->turno->nombre_vendedor)
                        .', que no se ha cerrado.')->values()->all(),
                ]);
            }

            $numero = (int) Turno::query()->lockForUpdate()->max('numero_turno') + 1;
            $iniciales = $this->ultimasLecturas();

            $turno = Turno::create([
                'fecha' => $datos['fecha'],
                'numero_turno' => $numero,
                'estado' => Turno::ESTADO_ABIERTA,
                'turno_dia' => (int) $datos['turno_dia'],
                'hora_inicio' => $datos['hora_inicio'],
                'nombre_vendedor' => $usuario->name,
                'user_id' => $usuario->id,
                'precio_corriente' => (float) FuelPrice::precioParaTurno('Gasolina', $numero),
                'precio_acpm' => (float) FuelPrice::precioParaTurno('ACPM', $numero),
            ]);

            foreach (TurnoSurtidor::MANGUERAS as $manguera => $combustible) {
                if (! in_array($manguera, $datos['mangueras'], true)) {
                    continue;
                }

                $turno->surtidores()->create([
                    'manguera' => $manguera,
                    'combustible' => $combustible,
                    'lectura_inicial' => $iniciales[$manguera] ?? 0,
                    'lectura_final' => null,
                    'galones' => 0,
                    'total' => 0,
                ]);
            }

            return $turno;
        });
    }

    /**
     * Cierra la planilla: exige la lectura final de cada manguera, no menor que la inicial.
     */
    public function cerrar(Turno $turno, User $usuario): void
    {
        $errores = [];
        $surtidores = $turno->surtidores()->get();

        if ($surtidores->isEmpty()) {
            $errores[] = 'La planilla no tiene mangueras.';
        }

        foreach ($surtidores as $surtidor) {
            if ($surtidor->lectura_final === null) {
                $errores[] = 'Falta la lectura final de '.$surtidor->manguera.'.';
            } elseif ($surtidor->tieneCambioContador() && (float) $surtidor->lectura_final < $surtidor->lecturaMinimaFinal()) {
                $errores[] = 'La lectura final de '.$surtidor->manguera.' es menor que la del contador nuevo instalado.';
            } elseif ((float) $surtidor->lectura_final < $surtidor->lecturaMinimaFinal()) {
                $errores[] = 'La lectura final de '.$surtidor->manguera.' es menor que la inicial. Si se cambió el contador, '
                    .'el administrador debe registrar el CAMBIO DE CONTADOR.';
            }
        }

        if ($errores) {
            throw ValidationException::withMessages(['cerrar' => $errores]);
        }

        $turno->update([
            'estado' => Turno::ESTADO_CERRADA,
            'cerrado_at' => now(),
            'cerrado_por' => $usuario->name,
        ]);
    }
}
