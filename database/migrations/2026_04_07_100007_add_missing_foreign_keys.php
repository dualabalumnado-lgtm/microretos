<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Las columnas ciclo_id y familia_id se crearon en intentos previos pero sin
 * sus FK constraints (el primer intento falló en la constraint, no en la columna).
 * Esta migración solo añade las constraints que faltan.
 */
return new class extends Migration
{
    private function fkExists(string $table, string $constraint): bool
    {
        return DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraint)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->exists();
    }

    public function up(): void
    {
        // ciclo_id quedó como BIGINT UNSIGNED del intento fallido anterior.
        // ciclos_formativos.id es INT (firmado) → hay que igualar el tipo antes del constraint.
        DB::statement('ALTER TABLE microretos MODIFY ciclo_id INT NULL');
        // familias.id es INT UNSIGNED → familia_id debe ser INT UNSIGNED también
        DB::statement('ALTER TABLE empresa_familia MODIFY familia_id INT UNSIGNED NULL');

        if (!$this->fkExists('microretos', 'microretos_ciclo_id_foreign')) {
            Schema::table('microretos', function (Blueprint $table) {
                $table->foreign('ciclo_id')->references('id')->on('ciclos_formativos')->onDelete('restrict');
            });
        }

        if (!$this->fkExists('empresa_familia', 'empresa_familia_familia_id_foreign')) {
            Schema::table('empresa_familia', function (Blueprint $table) {
                $table->foreign('familia_id')->references('id')->on('familias')->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        Schema::table('microretos', function (Blueprint $table) {
            $table->dropForeign(['ciclo_id']);
        });
        Schema::table('empresa_familia', function (Blueprint $table) {
            $table->dropForeign(['familia_id']);
        });
    }
};
