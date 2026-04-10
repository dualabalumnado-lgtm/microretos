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
            CREATE TABLE `ciclos_formativos` (
                `id`            INT NOT NULL AUTO_INCREMENT,
                `idCiclo`       INT NOT NULL DEFAULT 0,
                `nombre`        VARCHAR(255) NOT NULL,
                `familia`       VARCHAR(255) DEFAULT NULL,
                `familia_id`    INT UNSIGNED DEFAULT NULL,
                `grado`         VARCHAR(100) DEFAULT NULL,
                `referenciaBOE` VARCHAR(50)  DEFAULT NULL,
                `siglasGrado`   VARCHAR(3)   NOT NULL,
                PRIMARY KEY (`id`),
                KEY `ciclos_formativos_familia_id_foreign` (`familia_id`),
                CONSTRAINT `ciclos_formativos_familia_id_foreign`
                    FOREIGN KEY (`familia_id`) REFERENCES `familias` (`id`)
                    ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('ciclos_formativos');
    }
};
