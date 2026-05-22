<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EnergiasRenovablesSeeder extends Seeder
{
    public function run(): void
    {
        // Idempotente: si ya hay módulos para este ciclo, no hace nada
        $cicloId = DB::table('ciclos_formativos')
            ->where('referenciaBOE', 'RD 1584/2011')
            ->value('id');

        if ($cicloId && DB::table('modulos')->where('idcicloformativo', $cicloId)->exists()) {
            $this->command->info('EnergiasRenovablesSeeder: datos ya presentes, se omite.');
            return;
        }

        $sql = file_get_contents(database_path('seeders/energias_renovables_seed.sql'));
        DB::unprepared($sql);

        $this->command->info('EnergiasRenovablesSeeder: familia, ciclo, módulos, RAs y CEs insertados.');
    }
}
