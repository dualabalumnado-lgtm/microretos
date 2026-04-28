<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Añadir columna temporal entera
        Schema::table('sesiones', function (Blueprint $table) {
            $table->unsignedBigInteger('microreto_id_new')->nullable()->after('id');
        });

        // 2. Poblar desde microretos.uuid (convierte los UUID strings existentes a IDs enteros)
        DB::statement('
            UPDATE sesiones s
            INNER JOIN microretos m ON m.uuid = s.microreto_id
            SET s.microreto_id_new = m.id
        ');

        // 3. Eliminar columna string antigua
        Schema::table('sesiones', function (Blueprint $table) {
            $table->dropColumn('microreto_id');
        });

        // 4. Renombrar temporal a microreto_id
        Schema::table('sesiones', function (Blueprint $table) {
            $table->renameColumn('microreto_id_new', 'microreto_id');
        });

        // 5. Añadir FK constraint
        Schema::table('sesiones', function (Blueprint $table) {
            $table->foreign('microreto_id')
                  ->references('id')->on('microretos')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sesiones', function (Blueprint $table) {
            $table->dropForeign(['microreto_id']);
            $table->dropColumn('microreto_id');
        });

        Schema::table('sesiones', function (Blueprint $table) {
            $table->string('microreto_id', 36)->nullable();
        });
    }
};
