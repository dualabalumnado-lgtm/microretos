<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('centro_ciclo', function (Blueprint $table) {
            // Añadimos centro_id FK que convive con 'centro_educativo' (string) hasta backfill
            if (!Schema::hasColumn('centro_ciclo', 'centro_id')) {
                $table->unsignedBigInteger('centro_id')->nullable()->after('id');
                $table->foreign('centro_id')->references('id')->on('centro_educativo')->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('centro_ciclo', function (Blueprint $table) {
            $table->dropForeign(['centro_id']);
            $table->dropColumn('centro_id');
        });
    }
};
