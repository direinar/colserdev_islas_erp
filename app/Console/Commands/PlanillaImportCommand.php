<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TurnoImportService;
use App\Services\TurnoCalculatorService;
use App\Actions\GuardarTurnoAction;
use App\DTOs\TurnoDTO;

class PlanillaImportCommand extends Command
{
    protected $signature = 'planilla:import {file?}';

    protected $description = 'Importa la planilla Excel (carpeta docs) y crea un Turno.';

    public function handle()
    {
        $file = $this->argument('file');

        if (!$file) {
            // search docs for file starting with PLANTILLA
            $candidates = glob(base_path('docs/PLANTILLA*')) ?: glob(base_path('docs/PLANTILLA*.*'));
            if (empty($candidates)) {
                $this->error('No se encontró el archivo PLANTILLA en docs/. Pasa la ruta como argumento.');
                return 1;
            }
            $file = $candidates[0];
        }

        $this->info('Importando: ' . $file);

        if (ini_get('memory_limit') !== '-1') {
            @ini_set('memory_limit', '512M');
        }

        $importer = new TurnoImportService();
        try {
            $dto = $importer->import($file, 'planilla de turnos');
        } catch (\Throwable $e) {
            $this->error('Error importando: ' . $e->getMessage());
            return 1;
        }

        $calculator = new TurnoCalculatorService();
        $totals = $calculator->calculateFromDTO($dto);

        // merge totals into dto
        foreach ($totals as $k => $v) {
            $dto->set($k, $v);
        }

        $guard = new GuardarTurnoAction();
        $turno = $guard->execute($dto);

        $this->info('Turno creado: id=' . $turno->id);
        return 0;
    }
}
