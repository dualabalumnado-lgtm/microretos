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
            CREATE TABLE `resultados_aprendizaje` (
                `id`       INT NOT NULL AUTO_INCREMENT,
                `idmodulo` INT NOT NULL,
                `ra`       VARCHAR(1000) NOT NULL,
                PRIMARY KEY (`id`),
                KEY `ra_idmodulo_index` (`idmodulo`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('resultados_aprendizaje');
    }
};
