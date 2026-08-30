<?php

namespace Database\Seeders;

use App\Models\Turno;
use Illuminate\Database\Seeder;

class TurnoVentasSeeder extends Seeder
{
    /**
     * Seed default turno sales rows used by the traditional turn form.
     */
    public function run(): void
    {
        $turno = Turno::firstOrCreate(
            [
                'fecha' => now()->toDateString(),
                'numero_turno' => 1,
            ],
            [
                'nombre_vendedor' => 'Vendedor Demo',
                'precio_corriente' => config('combustibles.corriente', 0),
                'precio_acpm' => config('combustibles.acpm', 0),
            ]
        );

        $rows = [
            ['surtidor' => 'SURTIDOR 1 CTE', 'combustible' => 'CTE', 'galones' => 4.760, 'valor' => 78540],
            ['surtidor' => 'SURTIDOR 1 ACPM', 'combustible' => 'ACPM', 'galones' => 1.110, 'valor' => 10934],
            ['surtidor' => 'SURTIDOR 2 CTE', 'combustible' => 'CTE', 'galones' => 100.000, 'valor' => 1650000],
            ['surtidor' => 'SURTIDOR 2 ACPM', 'combustible' => 'ACPM', 'galones' => 4.000, 'valor' => 39400],
            ['surtidor' => 'SURTIDOR 3 ACPM', 'combustible' => 'ACPM', 'galones' => 0.000, 'valor' => 0],
            ['surtidor' => 'SURTIDOR 3 CTE', 'combustible' => 'CTE', 'galones' => 2.000, 'valor' => 33000],
        ];

        foreach ($rows as $row) {
            $turno->ventas()->firstOrCreate(
                [
                    'surtidor' => $row['surtidor'],
                ],
                [
                    'combustible' => $row['combustible'],
                    'galones' => $row['galones'],
                    'valor' => $row['valor'],
                ]
            );
        }

        $lecturas = [
            ['manguera' => 'PLUS 01', 'combustible' => 'corriente', 'lectura_inicial' => 13354.969, 'lectura_final' => 13395.969],
            ['manguera' => 'PLUS 02', 'combustible' => 'corriente', 'lectura_inicial' => 75485.280, 'lectura_final' => 75551.080],
            ['manguera' => 'ACPM 03', 'combustible' => 'acpm', 'lectura_inicial' => 33472.215, 'lectura_final' => 33477.125],
            ['manguera' => 'ACPM 04', 'combustible' => 'acpm', 'lectura_inicial' => 70397.550, 'lectura_final' => 70397.550],
            ['manguera' => 'PLUS 05', 'combustible' => 'corriente', 'lectura_inicial' => 287725.093, 'lectura_final' => 287725.093],
            ['manguera' => 'PLUS 06', 'combustible' => 'corriente', 'lectura_inicial' => 713598.443, 'lectura_final' => 713598.443],
            ['manguera' => 'ACPM 07', 'combustible' => 'acpm', 'lectura_inicial' => 337932.919, 'lectura_final' => 337932.919],
            ['manguera' => 'ACPM 08', 'combustible' => 'acpm', 'lectura_inicial' => 654631.029, 'lectura_final' => 654631.029],
            ['manguera' => 'PLUS 09', 'combustible' => 'corriente', 'lectura_inicial' => 722373.310, 'lectura_final' => 722373.310],
            ['manguera' => 'PLUS 10', 'combustible' => 'corriente', 'lectura_inicial' => 695498.984, 'lectura_final' => 695498.984],
            ['manguera' => 'ACPM 11', 'combustible' => 'acpm', 'lectura_inicial' => 448217.218, 'lectura_final' => 448217.218],
            ['manguera' => 'ACPM 12', 'combustible' => 'acpm', 'lectura_inicial' => 626804.975, 'lectura_final' => 626804.975],
        ];

        foreach ($lecturas as $lectura) {
            $turno->surtidores()->firstOrCreate(
                [
                    'manguera' => $lectura['manguera'],
                ],
                [
                    'combustible' => $lectura['combustible'],
                    'lectura_inicial' => $lectura['lectura_inicial'],
                    'lectura_final' => $lectura['lectura_final'],
                    'galones' => $lectura['lectura_final'] - $lectura['lectura_inicial'],
                ]
            );
        }
    }
}
