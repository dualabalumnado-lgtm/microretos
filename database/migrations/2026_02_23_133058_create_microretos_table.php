<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('microretos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->string('empresa_nombre')->nullable();
            
            // Textos descriptivos
            $table->text('quien_es');
            $table->text('dia_a_dia');
            $table->text('pregunta_reto');
            
            // Arrays (JSON) para las listas con viñetas
            $table->json('dificultades')->nullable();
            $table->json('que_necesitan')->nullable();
            $table->json('limitaciones')->nullable();
            $table->json('prototipos')->nullable();
            $table->json('ods_sugeridos')->nullable();
            $table->json('soft_skills')->nullable();
            
            // Arrays (JSON) Académicos
            $table->json('evaluacion_oficial')->nullable(); // Módulo, RA, CE
            $table->json('tips_profesorado')->nullable(); // Solo profe
            
            // Metadatos
            $table->string('nivel_grupo')->nullable();
            $table->string('ciclo')->nullable();
            $table->string('modulo')->nullable();
            $table->string('duracion')->nullable();
            
            $table->timestamps();
        });
    }
    public function down() {
        Schema::dropIfExists('microretos');
    }
};