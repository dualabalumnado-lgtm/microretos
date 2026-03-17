<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->renameColumn('nombre_comercial', 'temp_columna');
        });
        Schema::table('empresas', function (Blueprint $table) {
            $table->renameColumn('razon_social', 'nombre_comercial');
        });
        Schema::table('empresas', function (Blueprint $table) {
            $table->renameColumn('temp_columna', 'razon_social');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->renameColumn('razon_social', 'temp_columna');
        });
        Schema::table('empresas', function (Blueprint $table) {
            $table->renameColumn('nombre_comercial', 'razon_social');
        });
        Schema::table('empresas', function (Blueprint $table) {
            $table->renameColumn('temp_columna', 'nombre_comercial');
        });
    }
};