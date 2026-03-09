<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('empresa_familia', function (Blueprint $table) {
            $table->id();
            
            // Relacionamos con la ID de la empresa
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            
            // Ponemos el nombre de la Familia Profesional
            $table->string('familia');
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('empresa_familia');
    }
};