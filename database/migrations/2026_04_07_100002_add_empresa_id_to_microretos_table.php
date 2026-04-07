<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('microretos', function (Blueprint $table) {
            // FK nullable: se rellena con el script de backfill antes de hacerla NOT NULL
            if (!Schema::hasColumn('microretos', 'empresa_id')) {
                $table->unsignedBigInteger('empresa_id')->nullable()->after('titulo');
                $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('restrict');
            }
        });
    }

    public function down(): void
    {
        Schema::table('microretos', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
            $table->dropColumn('empresa_id');
        });
    }
};
