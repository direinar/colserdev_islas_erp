<?php

namespace App\Services\Nomina;

use App\Models\Nomina\ParametroLiquidacion;
use App\Models\Nomina\Turno;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Calcula las horas trabajadas, las de recargo nocturno (RN) y las de recargo
 * dominical/festivo (RF) de un turno en una fecha, con las franjas horarias de
 * la vigencia de parámetros. Un turno que pasa la medianoche se reparte entre
 * los dos días: cada minuto se clasifica según el día y la hora en que cae.
 */
class CalculoHorasTurnoService
{
    /**
     * @param  list<string>  $fechasFestivas  Fechas Y-m-d festivas (los domingos se detectan solos).
     * @return array{horas_trabajadas: float, horas_nocturnas: float, horas_festivas: float}
     */
    public function calcular(Turno $turno, CarbonInterface $fecha, ParametroLiquidacion $parametros, array $fechasFestivas = []): array
    {
        $festivas = array_flip($fechasFestivas);
        $diaDelTurno = CarbonImmutable::parse($fecha->toDateString());
        $minutoInicio = $turno->minutoInicio();
        $duracion = $turno->duracionEnMinutos();

        $minutosNocturnos = 0;
        $minutosFestivos = 0;
        $diasDominicales = [];

        for ($minuto = $minutoInicio; $minuto < $minutoInicio + $duracion; $minuto++) {
            $desfaseDias = intdiv($minuto, 1440);
            $minutoDelDia = $minuto % 1440;

            if ($this->estaEnFranja($minutoDelDia, (string) $parametros->hora_inicio_nocturna, (string) $parametros->hora_fin_nocturna)) {
                $minutosNocturnos++;
            }

            $diasDominicales[$desfaseDias] ??= $this->esDominicalOFestivo($diaDelTurno->addDays($desfaseDias), $festivas);

            if ($diasDominicales[$desfaseDias]
                && $this->estaEnFranjaDelDia($minutoDelDia, (string) $parametros->hora_inicio_festiva, (string) $parametros->hora_fin_festiva)) {
                $minutosFestivos++;
            }
        }

        return [
            'horas_trabajadas' => round($duracion / 60, 2),
            'horas_nocturnas' => round($minutosNocturnos / 60, 2),
            'horas_festivas' => round($minutosFestivos / 60, 2),
        ];
    }

    /**
     * Horas nocturnas del turno sin importar el día (para mostrar en el catálogo).
     */
    public function horasNocturnas(Turno $turno, ParametroLiquidacion $parametros): float
    {
        $minutosNocturnos = 0;
        $minutoInicio = $turno->minutoInicio();

        for ($minuto = $minutoInicio; $minuto < $minutoInicio + $turno->duracionEnMinutos(); $minuto++) {
            if ($this->estaEnFranja($minuto % 1440, (string) $parametros->hora_inicio_nocturna, (string) $parametros->hora_fin_nocturna)) {
                $minutosNocturnos++;
            }
        }

        return round($minutosNocturnos / 60, 2);
    }

    /**
     * @param  array<string, int>  $festivas
     */
    private function esDominicalOFestivo(CarbonImmutable $dia, array $festivas): bool
    {
        return $dia->isSunday() || isset($festivas[$dia->toDateString()]);
    }

    /**
     * Franja que puede cruzar la medianoche (p. ej. 19:00 a 06:00).
     */
    private function estaEnFranja(int $minutoDelDia, string $inicio, string $fin): bool
    {
        $desde = Turno::minutosDelDia($inicio);
        $hasta = Turno::minutosDelDia($fin);

        if ($desde < $hasta) {
            return $minutoDelDia >= $desde && $minutoDelDia < $hasta;
        }

        return $minutoDelDia >= $desde || $minutoDelDia < $hasta;
    }

    /**
     * Franja dentro del mismo día: si el fin es menor o igual al inicio (p. ej. 06:00 a 00:00),
     * llega hasta la medianoche de ese día.
     */
    private function estaEnFranjaDelDia(int $minutoDelDia, string $inicio, string $fin): bool
    {
        $desde = Turno::minutosDelDia($inicio);
        $hasta = Turno::minutosDelDia($fin);

        if ($hasta <= $desde) {
            $hasta = 1440;
        }

        return $minutoDelDia >= $desde && $minutoDelDia < $hasta;
    }
}
