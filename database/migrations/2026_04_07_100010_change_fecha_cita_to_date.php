<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Vaciamos strings en blanco primero
        DB::statement("UPDATE empresas SET fecha_cita = NULL WHERE fecha_cita = ''");

        // Limpiamos valores que no sean fechas válidas
        DB::statement("
            UPDATE empresas
            SET fecha_cita = NULL
            WHERE fecha_cita IS NOT NULL
              AND STR_TO_DATE(fecha_cita, '%Y-%m-%d') IS NULL
              AND STR_TO_DATE(fecha_cita, '%d/%m/%Y') IS NULL
        ");

        // Normalizamos formatos dd/mm/yyyy → yyyy-mm-dd
        DB::statement("
            UPDATE empresas
            SET fecha_cita = STR_TO_DATE(fecha_cita, '%d/%m/%Y')
            WHERE fecha_cita IS NOT NULL
              AND fecha_cita != ''
              AND fecha_cita REGEXP '^[0-9]{2}/[0-9]{2}/[0-9]{4}$'
        ");

        Schema::table('empresas', function (Blueprint $table) {
            $table->date('fecha_cita')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->string('fecha_cita')->nullable()->change();
        });
    }
};
