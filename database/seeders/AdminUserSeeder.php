<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Crea los administradores iniciales si no existen. Nunca cambia la clave de un usuario
     * existente (antes cada db:seed la devolvía a "password"). La clave inicial sale de
     * SEED_ADMIN_PASSWORD; si no está definida se genera una aleatoria y se muestra una vez.
     */
    public function run(): void
    {
        $administradores = [
            ['email' => 'diego.reina9@hotmail.com', 'name' => 'Diego Reina'],
            ['email' => 'carlosbeltranramos@gmail.com', 'name' => 'Carlos Beltrán'],
        ];

        foreach ($administradores as $administrador) {
            if (User::query()->where('email', $administrador['email'])->exists()) {
                continue;
            }

            $clave = (string) (env('SEED_ADMIN_PASSWORD') ?: Str::password(16));

            User::query()->create($administrador + [
                'password' => $clave,
                'role' => User::ROLE_ADMINISTRADOR,
                'email_verified_at' => now(),
            ]);

            if (! env('SEED_ADMIN_PASSWORD')) {
                $this->command?->warn("Clave inicial de {$administrador['email']}: {$clave} (cámbiela al ingresar).");
            }
        }
    }
}
