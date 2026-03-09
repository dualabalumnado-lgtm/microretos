<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('centro_ciclo', function (Blueprint $table) {
            $table->id();
            $table->string('centro_educativo'); // El nombre del colegio
            $table->unsignedBigInteger('ciclo_id'); // El ID del ciclo formativo
            
            // Opcional pero recomendado para que no se repitan
            $table->unique(['centro_educativo', 'ciclo_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('centro_ciclo');
    }
};