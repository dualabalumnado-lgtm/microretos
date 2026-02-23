<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('microretos', function (Blueprint $table) {
        $table->id();
        $table->string('titulo');
        $table->text('contexto_empresa');
        $table->text('reto_tecnico');
        // Validamos que puedan ser null por si la IA a veces no los genera igual
        $table->text('entregable_esperado')->nullable(); 
        $table->json('indicadores_resiliencia')->nullable();
        
        // Datos académicos y trazabilidad
        $table->json('ce_evaluados')->nullable(); // Guardamos el array de Criterios Elegidos
        $table->string('ciclo')->nullable();
        $table->string('modulo')->nullable();
        
        // Relación con el usuario/profesor (nullable por si queremos permitir guardado sin login)
        $table->foreignId('user_id')->nullable()->constrained(); 
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('microretos');
    }
};
