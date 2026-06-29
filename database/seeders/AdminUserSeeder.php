<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the application's administrator user.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'diego.reina9@hotmail.com'],
            [
                'name' => 'Diego Reina',
                'password' => 'password',
                'role' => User::ROLE_ADMINISTRADOR,
                'email_verified_at' => now(),
            ]
        );
    }
}
