<?php

namespace Database\Seeders;

use App\Services\CarteraSaldoInicialService;
use Illuminate\Database\Seeder;

/**
 * Carga única de los saldos iniciales de cartera al arrancar el sistema.
 * Lee database/seeders/data/saldos_iniciales.xlsx (mismo formato que la
 * importación del módulo: A = NIT/documento, B = fecha de corte, C = valor).
 * No se incluye en DatabaseSeeder; se ejecuta a mano:
 * php artisan db:seed --class=CarteraSaldoInicialSeeder
 */
class CarteraSaldoInicialSeeder extends Seeder
{
    public function run(CarteraSaldoInicialService $service): void
    {
        $archivo = database_path('seeders/data/saldos_iniciales.xlsx');

        if (! is_file($archivo)) {
            $this->command?->warn("No existe {$archivo}; no se cargaron saldos iniciales.");

            return;
        }

        $resultado = $service->importar($archivo);

        $this->command?->info("Saldos iniciales cargados: {$resultado['cargados']}.");

        foreach ($resultado['errores'] as $error) {
            $this->command?->error($error);
        }
    }
}
