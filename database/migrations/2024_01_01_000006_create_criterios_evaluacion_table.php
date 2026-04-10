<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Tabla de referencia académica (datos importados desde SQL dump).
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            CREATE TABLE `criterios_evaluacion` (
                `id`         INT NOT NULL AUTO_INCREMENT,
                `idmoduloRA` INT NOT NULL,
                `ce`         VARCHAR(2000) NOT NULL,
                PRIMARY KEY (`id`),
                KEY `ce_idmoduloRA_index` (`idmoduloRA`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('criterios_evaluacion');
    }
};
