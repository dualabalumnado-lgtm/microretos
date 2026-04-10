<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresa_familia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->unsignedInteger('familia_id')->nullable();
            $table->string('familia')->default(''); // legacy: nombre texto de familia, aún usado por controladores
            $table->timestamps();

            $table->foreign('familia_id')->references('id')->on('familias')->restrictOnDelete();
            $table->index('familia_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresa_familia');
    }
};
