<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('microretos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');

            // FKs normalizadas
            $table->foreignId('empresa_id')->nullable()->index()->constrained('empresas')->restrictOnDelete();
            $table->integer('ciclo_id')->nullable()->index();

            // Campo legacy: nombre texto de empresa (producción aún lo usa)
            $table->string('empresa_nombre')->nullable();

            // Contenido del reto
            $table->text('quien_es')->nullable();
            $table->text('dia_a_dia')->nullable();
            $table->text('pregunta_reto')->nullable();
            $table->json('dificultades')->nullable();
            $table->json('que_necesitan')->nullable();
            $table->json('limitaciones')->nullable();
            $table->json('prototipos')->nullable();
            $table->json('ods_sugeridos')->nullable();
            $table->json('soft_skills')->nullable();
            $table->json('evaluacion_oficial')->nullable();
            $table->json('tips_profesorado')->nullable();

            // Metadatos pedagógicos
            $table->string('nivel_grupo')->nullable();
            $table->string('ciclo')->nullable();   // legacy: nombre texto del ciclo
            $table->string('modulo')->nullable();  // legacy: nombre texto del módulo
            $table->string('duracion')->nullable();
            $table->timestamps();
            $table->boolean('es_simulado')->default(false);

            $table->foreign('ciclo_id')->references('id')->on('ciclos_formativos')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('microretos');
    }
};
