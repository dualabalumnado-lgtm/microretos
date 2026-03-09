<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('microretos', function (Blueprint $table) {
            $table->id();
            
            // Datos generados por la IA
            $table->string('titulo');
            $table->text('contexto_empresa');
            $table->text('reto_tecnico');
            $table->string('entregable_esperado')->nullable();
            
            // Como las soft skills vienen en array (ej: ["Resiliencia", "Empatía"]), lo guardamos como JSON
            $table->json('indicadores_resiliencia')->nullable(); 
            
            // El match académico de la IA
            $table->text('modulos_sugeridos')->nullable();
            $table->text('ra_ce_asociados')->nullable();
            
            // Datos del Filtro de DuaLab
            $table->string('ciclo')->nullable();
            $table->string('modulo')->nullable();
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('microretos');
    }
};