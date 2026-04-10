<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Tabla de referencia académica (datos importados desde SQL dump).
// Usa DB::statement para preservar tipos exactos (INT signed, sin timestamps)
// compatibles con los datos del BOE.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            CREATE TABLE `modulos` (
                `id`               INT NOT NULL AUTO_INCREMENT,
                `idAreaSC`         INT NOT NULL DEFAULT 0,
                `idcicloformativo` INT NOT NULL,
                `codigoBOE`        VARCHAR(6) DEFAULT \'\',
                `nombre`           VARCHAR(255) NOT NULL,
                `curso`            INT NOT NULL DEFAULT 1,
                `horastotales`     INT NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                KEY `modulos_idcicloformativo_index` (`idcicloformativo`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('modulos');
    }
};
