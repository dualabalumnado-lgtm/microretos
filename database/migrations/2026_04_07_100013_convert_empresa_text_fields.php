<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // friccion_area, restricciones y lo_que_no_quieren son VARCHAR(255).
    // Con la nueva UI el usuario puede escribir hasta 400-600 caracteres → necesitamos TEXT.
    // TEXT en MySQL soporta hasta 65.535 bytes — más que suficiente.
    // Usamos ALTER TABLE raw para evitar dependencia de doctrine/dbal en Laravel 11.

    public function up(): void
    {
        DB::statement('ALTER TABLE empresas MODIFY COLUMN friccion_area TEXT NULL');
        DB::statement('ALTER TABLE empresas MODIFY COLUMN restricciones TEXT NULL');
        DB::statement('ALTER TABLE empresas MODIFY COLUMN lo_que_no_quieren TEXT NULL');
    }

    public function down(): void
    {
        // Al revertir truncamos a 255 si hubiera datos más largos
        DB::statement('ALTER TABLE empresas MODIFY COLUMN friccion_area VARCHAR(255) NULL');
        DB::statement('ALTER TABLE empresas MODIFY COLUMN restricciones VARCHAR(255) NULL');
        DB::statement('ALTER TABLE empresas MODIFY COLUMN lo_que_no_quieren VARCHAR(255) NULL');
    }
};
