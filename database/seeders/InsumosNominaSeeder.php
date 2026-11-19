<?php

namespace Database\Seeders;

use App\Models\Nomina\Festivo;
use App\Models\Nomina\ParametroLiquidacion;
use App\Models\Nomina\Turno;
use App\Services\Nomina\FestivosColombiaService;
use Database\Factories\Nomina\ParametroLiquidacionFactory;
use Illuminate\Database\Seeder;

/**
 * Datos iniciales de los insumos: la tabla de parámetros 2026 de la guía de nómina,
 * los turnos que usa la planilla de la EDS y los festivos del año en curso y el
 * siguiente. Se puede ejecutar varias veces sin duplicar ni sobrescribir.
 */
class InsumosNominaSeeder extends Seeder
{
    public function run(FestivosColombiaService $festivosColombia): void
    {
        $parametros = (new ParametroLiquidacionFactory)->definition();
        if (ParametroLiquidacion::whereDate('vigente_desde', $parametros['vigente_desde'])->doesntExist()) {
            ParametroLiquidacion::create($parametros);
        }

        $turnos = [
            ['5AM-1PM', '05:00', '13:00', '#fff3cd'],
            ['6AM-1PM', '06:00', '13:00', '#fff3cd'],
            ['6AM-2PM', '06:00', '14:00', '#fff3cd'],
            ['8AM-2PM', '08:00', '14:00', '#fff3cd'],
            ['12PM-8PM', '12:00', '20:00', '#d1e7dd'],
            ['1PM-8PM', '13:00', '20:00', '#d1e7dd'],
            ['2PM-10PM', '14:00', '22:00', '#d1e7dd'],
            ['3PM-10PM', '15:00', '22:00', '#d1e7dd'],
            ['10PM-5AM', '22:00', '05:00', '#cfe2ff'],
            ['10PM-6AM', '22:00', '06:00', '#cfe2ff'],
        ];

        foreach ($turnos as [$nombre, $inicio, $fin, $color]) {
            Turno::firstOrCreate(['nombre' => $nombre], ['hora_inicio' => $inicio, 'hora_fin' => $fin, 'color' => $color]);
        }

        foreach ([now()->year, now()->year + 1] as $anio) {
            foreach ($festivosColombia->festivosDelAnio($anio) as $fecha => $nombre) {
                if (Festivo::whereDate('fecha', $fecha)->doesntExist()) {
                    Festivo::create(['fecha' => $fecha, 'nombre' => $nombre]);
                }
            }
        }
    }
}
