<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Seed default customers used across turnos and cartera.
     */
    public function run(): void
    {
        $customers = [
            ['name' => 'ARKATEC SAS', 'document' => '900306424'],
            ['name' => 'COMINSUMOS AGROPECUARIOS S.A.', 'document' => '901851228'],
            ['name' => 'CONSORCIO LIDER 2019', 'document' => '901346165'],
            ['name' => 'CULTIVOS FEIDRIA SAS', 'document' => '901679449'],
            ['name' => 'GUILLERMO GUIO GUERRERO', 'document' => '7184725'],
            ['name' => 'JHON ALEJANDRO MORALES CASTRO', 'document' => '7173462'],
            ['name' => 'JUAN CARLOS GIL LEON', 'document' => '1054708417'],
            ['name' => 'JULIO ARISTIPO HERNANDEZ LOPEZ', 'document' => '4043138'],
            ['name' => 'NOVACAMPO SAS SOS COMERC INTERNANCIONAL', 'document' => '830117784'],
            ['name' => 'OPERADORA MINERA HYM SAS', 'document' => '901614244'],
            ['name' => 'OPERADORA TYT SAS', 'document' => '901784963'],
            ['name' => 'ORLANDO GRIJALBA RODRIGUEZ', 'document' => '7168116'],
            ['name' => 'PATRIMONIOS AUTONOMOS FIDUCIARIA', 'document' => '830054539'],
            ['name' => 'PEDRO IGNACIO GONZALEZ CORDERO', 'document' => '74020025'],
            ['name' => 'WILSON GUMERCINDO CHIVATA', 'document' => '1049647939'],
        ];

        foreach ($customers as $customer) {
            Customer::firstOrCreate(
                ['document' => $customer['document']],
                ['name' => $customer['name']]
            );
        }
    }
}
