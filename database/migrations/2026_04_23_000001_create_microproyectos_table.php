<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('microproyectos', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Relaciones (tipos exactos alineados con cada tabla referenciada)
            $table->foreignId('microreto_id')->nullable()->constrained('microretos')->nullOnDelete();
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->nullOnDelete();
            $table->foreignId('centro_id')->nullable()->constrained('centro_educativo')->nullOnDelete();
            // familias.id es int unsigned (increments) — no bigint
            $table->unsignedInteger('familia_id')->nullable()->index();
            $table->foreign('familia_id')->references('id')->on('familias')->nullOnDelete();
            // ciclos_formativos.id es int firmado (CREATE TABLE con INT)
            $table->integer('ciclo_id')->nullable()->index();
            $table->foreign('ciclo_id')->references('id')->on('ciclos_formativos')->nullOnDelete();

            // Paso 1: Básicos
            $table->string('titulo');
            $table->string('curso')->nullable();

            // Pasos 2-13: Secciones del wizard (JSON por sección)
            $table->json('datos_empresa')->nullable();       // snapshot empresa + contacto
            $table->json('datos_centro')->nullable();        // snapshot centro + docente
            $table->json('equipo')->nullable();              // alumnos + roles
            $table->json('modulos_seleccionados')->nullable();
            $table->text('ra_ce')->nullable();               // resultados aprendizaje + criterios evaluación
            $table->json('fundamentacion')->nullable();      // paso opcional
            $table->json('diseno_reto')->nullable();         // descripción, contexto, restricciones
            $table->json('diseno_microproyecto')->nullable();// fases, entregables, cronograma
            $table->json('resumen')->nullable();
            $table->json('objetivos')->nullable();
            $table->json('kpis')->nullable();                // paso opcional
            $table->json('validacion_empresa')->nullable();  // respuestas formulario validación

            // Control de flujo
            $table->tinyInteger('paso_actual')->default(1);
            $table->enum('estado', ['borrador', 'publicado', 'archivado'])->default('borrador');
            $table->string('token_empresa')->nullable()->unique(); // acceso validación empresa
            $table->boolean('empresa_validado')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('microproyectos');
    }
};
