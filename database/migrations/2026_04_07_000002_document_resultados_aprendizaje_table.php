<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('resultados_aprendizaje')) {
            return;
        }

        Schema::create('resultados_aprendizaje', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('idmodulo');
            $table->string('ra', 1000);
            $table->string('descripcion')->nullable(); // alias usado en el controlador
            $table->timestamps();

            $table->foreign('idmodulo')->references('id')->on('modulos')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resultados_aprendizaje');
    }
};
