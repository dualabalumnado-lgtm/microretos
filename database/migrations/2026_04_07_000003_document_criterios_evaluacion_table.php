<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('criterios_evaluacion')) {
            return;
        }

        Schema::create('criterios_evaluacion', function (Blueprint $table) {
            $table->integer('id')->autoIncrement(); // el SQL original no tenía AUTO_INCREMENT — corregido aquí
            $table->integer('idmoduloRA');
            $table->string('ce', 2000);
            $table->string('descripcion')->nullable(); // alias usado en el controlador
            $table->timestamps();

            $table->foreign('idmoduloRA')->references('id')->on('resultados_aprendizaje')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('criterios_evaluacion');
    }
};
