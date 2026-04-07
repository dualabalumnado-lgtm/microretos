<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ciclos_formativos y modulos fueron creadas con utf8mb4_0900_ai_ci (MySQL 8 por defecto)
        // El resto de tablas usan utf8mb4_unicode_ci
        // La diferencia causó errores de collation en JOINs y backfills
        DB::statement('ALTER TABLE ciclos_formativos CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        DB::statement('ALTER TABLE modulos CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE ciclos_formativos CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci');
        DB::statement('ALTER TABLE modulos CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci');
    }
};
