<?php

namespace App\Services\Nomina;

use Carbon\CarbonImmutable;

/**
 * Festivos de Colombia de un año según la Ley 51 de 1983 (Ley Emiliani): unos
 * caen siempre en su fecha, otros se trasladan al lunes siguiente y los
 * religiosos móviles dependen del Domingo de Pascua.
 */
class FestivosColombiaService
{
    /**
     * @return array<string, string> Fecha Y-m-d => nombre del festivo, en orden cronológico.
     */
    public function festivosDelAnio(int $anio): array
    {
        $pascua = $this->domingoDePascua($anio);

        $festivos = [
            [CarbonImmutable::create($anio, 1, 1), 'Año Nuevo'],
            [$this->siguienteLunes(CarbonImmutable::create($anio, 1, 6)), 'Día de los Reyes Magos'],
            [$this->siguienteLunes(CarbonImmutable::create($anio, 3, 19)), 'Día de San José'],
            [$pascua->subDays(3), 'Jueves Santo'],
            [$pascua->subDays(2), 'Viernes Santo'],
            [CarbonImmutable::create($anio, 5, 1), 'Día del Trabajo'],
            [$this->siguienteLunes($pascua->addDays(39)), 'Ascensión del Señor'],
            [$this->siguienteLunes($pascua->addDays(60)), 'Corpus Christi'],
            [$this->siguienteLunes($pascua->addDays(68)), 'Sagrado Corazón de Jesús'],
            [$this->siguienteLunes(CarbonImmutable::create($anio, 6, 29)), 'San Pedro y San Pablo'],
            [CarbonImmutable::create($anio, 7, 20), 'Día de la Independencia'],
            [CarbonImmutable::create($anio, 8, 7), 'Batalla de Boyacá'],
            [$this->siguienteLunes(CarbonImmutable::create($anio, 8, 15)), 'Asunción de la Virgen'],
            [$this->siguienteLunes(CarbonImmutable::create($anio, 10, 12)), 'Día de la Raza'],
            [$this->siguienteLunes(CarbonImmutable::create($anio, 11, 1)), 'Día de Todos los Santos'],
            [$this->siguienteLunes(CarbonImmutable::create($anio, 11, 11)), 'Independencia de Cartagena'],
            [CarbonImmutable::create($anio, 12, 8), 'Día de la Inmaculada Concepción'],
            [CarbonImmutable::create($anio, 12, 25), 'Navidad'],
        ];

        $resultado = [];
        foreach ($festivos as [$fecha, $nombre]) {
            $resultado[$fecha->toDateString()] = $nombre;
        }
        ksort($resultado);

        return $resultado;
    }

    /**
     * Domingo de Pascua por el algoritmo gregoriano anónimo (Meeus/Jones/Butcher).
     */
    public function domingoDePascua(int $anio): CarbonImmutable
    {
        $a = $anio % 19;
        $b = intdiv($anio, 100);
        $c = $anio % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $mes = intdiv($h + $l - 7 * $m + 114, 31);
        $dia = (($h + $l - 7 * $m + 114) % 31) + 1;

        return CarbonImmutable::create($anio, $mes, $dia);
    }

    /**
     * La misma fecha si ya es lunes; si no, el lunes siguiente.
     */
    private function siguienteLunes(CarbonImmutable $fecha): CarbonImmutable
    {
        return $fecha->isMonday() ? $fecha : $fecha->next(CarbonImmutable::MONDAY);
    }
}
