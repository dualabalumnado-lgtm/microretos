<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('microretos', function (Blueprint $table) {
            // FK nullable: convive con la columna 'ciclo' (string) hasta completar el backfill
            if (!Schema::hasColumn('microretos', 'ciclo_id')) {
                // ciclos_formativos.id es INT firmado (no BIGINT UNSIGNED), hay que coincidir el tipo
                $table->integer('ciclo_id')->nullable()->after('empresa_id');
                $table->foreign('ciclo_id')->references('id')->on('ciclos_formativos')->onDelete('restrict');
            }
        });
    }

    public function down(): void
    {
        Schema::table('microretos', function (Blueprint $table) {
            $table->dropForeign(['ciclo_id']);
            $table->dropColumn('ciclo_id');
        });
    }
};
