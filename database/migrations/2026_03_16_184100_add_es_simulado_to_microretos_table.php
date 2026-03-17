<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('microretos', function (Blueprint $table) {
            // Añade la columna como booleana, por defecto falsa
            $table->boolean('es_simulado')->default(false)->after('empresa_nombre'); 
        });
    }

    public function down()
    {
        Schema::table('microretos', function (Blueprint $table) {
            $table->dropColumn('es_simulado');
        });
    }
};