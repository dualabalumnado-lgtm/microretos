<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('microproyecto_recursos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('microproyecto_id')
                  ->constrained('microproyectos')
                  ->cascadeOnDelete();
            $table->enum('tipo', ['video', 'documento']);
            $table->string('label')->nullable();
            $table->string('filename');
            $table->text('url');
            $table->string('public_id')->unique();
            $table->string('resource_type', 20); // 'video', 'image', 'raw'
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('size')->nullable(); // bytes
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('microproyecto_recursos');
    }
};
