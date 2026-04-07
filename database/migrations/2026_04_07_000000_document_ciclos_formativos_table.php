<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración documental: ciclos_formativos existía en BD sin migración Laravel.
 * Se crea aquí para que migrate:fresh funcione correctamente.
 * Debe ejecutarse ANTES que las FK que la referencian (prefijo 000000).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ciclos_formativos')) {
            return; // La tabla ya existe con datos, no tocamos nada
        }

        Schema::create('ciclos_formativos', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('idCiclo')->default(0);
            $table->string('nombre');
            $table->unsignedBigInteger('familia_id')->nullable();
            $table->string('familia')->nullable(); // columna legacy (string)
            $table->string('grado', 100)->nullable();
            $table->string('referenciaBOE', 50)->nullable();
            $table->string('siglasGrado', 3)->nullable();
            $table->timestamps();

            $table->foreign('familia_id')->references('id')->on('familias')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ciclos_formativos');
    }
};
