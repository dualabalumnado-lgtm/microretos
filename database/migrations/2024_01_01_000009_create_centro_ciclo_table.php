<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('centro_ciclo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('centro_id')->nullable()->index()->constrained('centro_educativo')->cascadeOnDelete();
            $table->string('centro_educativo'); // legacy: nombre texto del centro, usado en firstOrCreate por controladores
            $table->unsignedBigInteger('ciclo_id');

            // La unicidad se basa en el par (centro_educativo, ciclo_id) que es lo que
            // usa firstOrCreate en los controladores. Cuando se migre el código a usar
            // centro_id, este unique deberá actualizarse a (centro_id, ciclo_id).
            $table->unique(['centro_educativo', 'ciclo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('centro_ciclo');
    }
};
