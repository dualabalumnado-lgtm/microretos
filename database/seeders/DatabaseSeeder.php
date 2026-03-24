<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
   public function run(): void
{
    \App\Models\User::updateOrCreate(
        ['email' => env('ADMIN_EMAIL')],
        [
            'name'     => 'Administrador DuaLab',
            'password' => bcrypt(env('ADMIN_PASSWORD')),
        ]
    );
}
}