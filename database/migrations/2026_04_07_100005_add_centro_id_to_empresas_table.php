<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            // FK nullable: convive con 'centro_educativo' (string) hasta backfill
            if (!Schema::hasColumn('empresas', 'centro_id')) {
                $table->unsignedBigInteger('centro_id')->nullable()->after('nombre_comercial');
                $table->foreign('centro_id')->references('id')->on('centro_educativo')->onDelete('restrict');
            }
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropForeign(['centro_id']);
            $table->dropColumn('centro_id');
        });
    }
};
