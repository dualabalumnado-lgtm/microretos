<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // PASO 1: Añadir la columna nullable (sin constraint todavía)
        // No usamos hasColumn para saltarnos la ejecución: si la columna ya existe
        // la instrucción ALTER fallará con un error claro. La migramos explícitamente.
        if (!Schema::hasColumn('ciclos_formativos', 'familia_id')) {
            Schema::table('ciclos_formativos', function (Blueprint $table) {
                $table->unsignedBigInteger('familia_id')->nullable()->after('familia');
            });
        }

        // PASO 2: Backfill — rellenamos familia_id haciendo JOIN por nombre
        // La columna 'familia' (string) ya contiene el nombre exacto que está en familias.nombre
        // El diagnóstico previo confirmó 0 ciclos sin match → ningún dato se pierde.
        DB::statement("
            UPDATE ciclos_formativos cf
            JOIN familias f ON f.nombre = cf.familia COLLATE utf8mb4_unicode_ci
            SET cf.familia_id = f.id
            WHERE cf.familia_id IS NULL
        ");

        // PASO 3: Añadir FK constraint (solo si no existe ya)
        $fkExists = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'ciclos_formativos')
            ->where('CONSTRAINT_NAME', 'ciclos_formativos_familia_id_foreign')
            ->exists();

        if (!$fkExists) {
            Schema::table('ciclos_formativos', function (Blueprint $table) {
                $table->foreign('familia_id')
                      ->references('id')
                      ->on('familias')
                      ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('ciclos_formativos', function (Blueprint $table) {
            if (Schema::hasColumn('ciclos_formativos', 'familia_id')) {
                $table->dropForeign(['familia_id']);
                $table->dropColumn('familia_id');
            }
        });
    }
};
