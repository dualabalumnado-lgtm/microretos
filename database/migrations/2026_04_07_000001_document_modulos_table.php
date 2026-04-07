<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('modulos')) {
            return;
        }

        Schema::create('modulos', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('idAreaSC')->default(0);
            $table->integer('idcicloformativo');
            $table->string('codigoBOE', 6)->nullable()->default('');
            $table->string('nombre');
            $table->integer('curso')->default(1);
            $table->integer('horastotales')->default(0);
            $table->timestamps();

            $table->foreign('idcicloformativo')->references('id')->on('ciclos_formativos')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modulos');
    }
};
