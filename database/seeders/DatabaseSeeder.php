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
    // Import all reference and project data
    $sql = file_get_contents(database_path('seeders/data_seed.sql'));
    \Illuminate\Support\Facades\DB::unprepared($sql);

    // Create admin user (credentials from .env — see .env.example)
    \App\Models\User::updateOrCreate(
        ['email' => env('ADMIN_EMAIL')],
        [
            'name'     => 'Administrador DuaLab',
            'password' => bcrypt(env('ADMIN_PASSWORD')),
        ]
    );

    $this->call(DemosSeeder::class);
    $this->call(MicorretosDemoSeeder::class);
}
}