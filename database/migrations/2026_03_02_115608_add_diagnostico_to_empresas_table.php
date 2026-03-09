<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('empresas', function (Blueprint $table) {
            // Añadimos las nuevas columnas para el contexto del problema (Todas nullables)
            $table->text('dia_a_normal')->nullable();
            $table->string('friccion_area')->nullable();
            $table->text('friccion_problema')->nullable();
            $table->text('consecuencias')->nullable(); // Lo guardaremos como texto separado por comas
            $table->string('restricciones')->nullable();
            $table->string('lo_que_no_quieren')->nullable();
        });
    }

    public function down()
    {
        Schema::table('empresas', function (Blueprint $table) {
            // Por si algún día queremos revertir la migración
            $table->dropColumn([
                'dia_a_normal', 
                'friccion_area', 
                'friccion_problema', 
                'consecuencias', 
                'restricciones', 
                'lo_que_no_quieren'
            ]);
        });
    }
};