<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresa_familia', function (Blueprint $table) {
            // Añadimos familia_id FK que convive con la columna 'familia' (string) hasta backfill
            if (!Schema::hasColumn('empresa_familia', 'familia_id')) {
                // familias.id es INT firmado (creado fuera de Laravel), hay que coincidir el tipo
                $table->integer('familia_id')->nullable()->after('empresa_id');
                $table->foreign('familia_id')->references('id')->on('familias')->onDelete('restrict');
            }
        });
    }

    public function down(): void
    {
        Schema::table('empresa_familia', function (Blueprint $table) {
            $table->dropForeign(['familia_id']);
            $table->dropColumn('familia_id');
        });
    }
};
