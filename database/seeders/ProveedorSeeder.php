<?php

namespace Database\Seeders;

use App\Models\Proveedor;
use Illuminate\Database\Seeder;

class ProveedorSeeder extends Seeder
{
    /**
     * Seed default proveedores used across compras de lubricantes.
     */
    public function run(): void
    {
        $proveedores = [
            ['name' => 'CHEVRON PETROLEUM COMPANY', 'document' => '860005223'],
            ['name' => 'HIDROTECNIK SAS', 'document' => '900426910'],
            ['name' => 'LUBRICANTES EL SOL SAS', 'document' => '900387475'],
        ];

        foreach ($proveedores as $proveedor) {
            Proveedor::firstOrCreate(
                ['document' => $proveedor['document']],
                ['name' => $proveedor['name']]
            );
        }
    }
}
