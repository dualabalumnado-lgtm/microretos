<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('empresas', function (Blueprint $table) {
            if (!Schema::hasColumn('empresas', 'tamano')) {
                $table->string('tamano')->nullable()->after('sector');
            }
            if (!Schema::hasColumn('empresas', 'dia_a_normal')) {
                $table->text('dia_a_normal')->nullable();
            }
            if (!Schema::hasColumn('empresas', 'friccion_area')) {
                $table->string('friccion_area')->nullable();
            }
            if (!Schema::hasColumn('empresas', 'friccion_problema')) {
                $table->text('friccion_problema')->nullable();
            }
            if (!Schema::hasColumn('empresas', 'consecuencias')) {
                $table->text('consecuencias')->nullable();
            }
            if (!Schema::hasColumn('empresas', 'restricciones')) {
                $table->string('restricciones')->nullable();
            }
            if (!Schema::hasColumn('empresas', 'lo_que_no_quieren')) {
                $table->string('lo_que_no_quieren')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('empresas', function (Blueprint $table) {
            // Elimina las columnas si hacemos un rollback
            $columnas = ['tamano', 'dia_a_normal', 'friccion_area', 'friccion_problema', 'consecuencias', 'restricciones', 'lo_que_no_quieren'];
            foreach ($columnas as $col) {
                if (Schema::hasColumn('empresas', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};