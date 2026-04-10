<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demos', function (Blueprint $table) {
            $table->id();

            // ── Identificación ──────────────────────────────────────────
            $table->string('familia_profesional');          // clave de búsqueda
            $table->string('etiqueta');                     // nombre legible para el selector

            // ── Paso 1: Datos de empresa (simulada) ─────────────────────
            $table->string('empresa_nombre');
            $table->string('empresa_sector');
            $table->string('empresa_tamano');
            $table->string('empresa_web')->nullable();
            $table->string('empresa_centro')->nullable();   // centro educativo asociado

            // ── Paso 2: Diagnóstico ──────────────────────────────────────
            $table->text('dia_a_normal');
            $table->string('friccion_area');
            $table->text('friccion_problema');
            $table->json('restricciones')->nullable();      // array de chips seleccionados
            $table->text('otra_limitacion')->nullable();
            $table->string('lo_que_no_quieren')->nullable();
            $table->json('consecuencias')->nullable();      // array de chips seleccionados
            $table->string('otra_consecuencia')->nullable();
            $table->text('expectativas_alumno')->nullable();

            // ── Paso 3: Match académico ───────────────────────────────────
            $table->string('nivel_grupo')->default('Medio');// Básico / Medio / Alto
            $table->unsignedTinyInteger('curso_seleccionado')->default(2);
            $table->string('duracion')->default('1 a 2 semanas');
            $table->unsignedTinyInteger('cantidad_microretos')->default(3);

            $table->timestamps();

            $table->unique('familia_profesional');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demos');
    }
};