<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->string('cif')->nullable();
            $table->string('nombre_comercial');
            $table->foreignId('centro_id')->nullable()->index()->constrained('centro_educativo')->restrictOnDelete();
            $table->string('centro_educativo')->nullable(); // legacy: nombre texto del centro, aún usado por controladores
            $table->string('razon_social')->nullable();
            $table->string('telefono')->nullable();
            $table->string('email_general')->nullable();
            $table->string('estado_contacto')->nullable();
            $table->date('fecha_cita')->nullable();
            $table->string('persona_contacto')->nullable();
            $table->string('email_contacto')->nullable();
            $table->string('posicion_contacto')->nullable();
            $table->string('sector')->nullable();
            $table->string('tamano')->nullable();
            $table->text('actividad')->nullable();
            $table->string('horario_atencion')->nullable();
            $table->string('direccion')->nullable();
            $table->string('numero')->nullable();
            $table->string('otros_direccion')->nullable();
            $table->string('codigo_postal')->nullable();
            $table->text('municipio')->nullable();
            $table->text('provincia')->nullable();
            $table->text('web')->nullable();
            $table->text('proyecto_asociado')->nullable();
            $table->timestamps();
            $table->text('dia_a_normal')->nullable();
            $table->text('friccion_area')->nullable();
            $table->text('friccion_problema')->nullable();
            $table->text('consecuencias')->nullable();
            $table->text('restricciones')->nullable();
            $table->text('lo_que_no_quieren')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};
