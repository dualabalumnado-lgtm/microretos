<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sesiones', function (Blueprint $table) {
            $table->id();
            $table->string('microreto_id', 36)->nullable(); // UUID, sin FK estricta
            $table->string('microreto_titulo');
            $table->date('fecha');
            $table->string('centro_educativo')->nullable();
            $table->string('ciclo_formativo')->nullable();
            $table->string('curso', 10)->nullable();
            $table->string('grupo', 10)->nullable();
            $table->unsignedSmallInteger('num_alumnos')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesiones');
    }
};
