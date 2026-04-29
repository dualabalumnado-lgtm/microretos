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
    \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0');

    // Limpiar tablas antes de importar (idempotente)
    foreach ([
        'microreto_tokens', 'microretos',
        'resultados_aprendizaje', 'criterios_evaluacion',
        'modulos', 'centro_ciclo',
        'ciclos_formativos', 'empresa_familia',
        'empresas', 'centro_educativo', 'familias', 'demos',
    ] as $tabla) {
        \Illuminate\Support\Facades\DB::table($tabla)->truncate();
    }

    // MySQL 8.4 InnoDB ignora UNIQUE_CHECKS=0 en columnas NOT NULL UNIQUE.
    // Eliminamos el índice temporalmente para poder importar el dump antiguo (sin UUIDs).
    try {
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE microretos DROP INDEX microretos_uuid_unique');
    } catch (\Exception $e) {
        // El índice ya no existe — no pasa nada
    }

    $sql = file_get_contents(database_path('seeders/data_seed.sql'));
    \Illuminate\Support\Facades\DB::unprepared($sql);

    \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1');

    // Backfill: MySQL UUID() genera un valor distinto por fila en un solo UPDATE
    \Illuminate\Support\Facades\DB::statement(
        "UPDATE microretos SET uuid = UUID() WHERE uuid IS NULL OR uuid = ''"
    );

    // Restaurar índice unique
    \Illuminate\Support\Facades\DB::statement(
        'ALTER TABLE microretos ADD UNIQUE INDEX microretos_uuid_unique (uuid)'
    );

    // Create admin user (credentials from .env — see .env.example)
    \App\Models\User::updateOrCreate(
        ['email' => env('ADMIN_EMAIL')],
        [
            'name'     => 'Administrador DuaLab',
            'password' => bcrypt(env('ADMIN_PASSWORD')),
        ]
    );

    $this->call(EnergiasRenovablesSeeder::class);
    $this->call(DemosSeeder::class);
    $this->call(MicorretosDemoSeeder::class);
}
}