<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            
            // Datos fiscales y de nombre
            $table->string('cif')->nullable(); 
            $table->string('nombre_comercial'); // Lo usamos como Razón Social obligatoria
            $table->string('razon_social')->nullable(); 
            
            // Datos de contacto general
            $table->string('telefono')->nullable(); 
            $table->string('email_general')->nullable(); 
            
            // Estado del CRM
            $table->string('estado_contacto')->nullable(); 
            $table->string('fecha_cita')->nullable(); 
            
            // Persona de contacto directa
            $table->string('persona_contacto')->nullable(); 
            $table->string('email_contacto')->nullable(); 
            $table->string('posicion_contacto')->nullable(); 
            
            // Actividad
            $table->string('sector')->nullable(); 
            $table->text('actividad')->nullable(); 
            $table->string('horario_atencion')->nullable(); 
            
            // Ubicación
            $table->string('direccion')->nullable(); 
            $table->string('numero')->nullable(); 
            $table->string('otros_direccion')->nullable(); 
            $table->string('codigo_postal')->nullable(); 
            $table->string('municipio')->nullable(); 
            $table->string('provincia')->nullable(); 
            
            // Extra
            $table->string('web')->nullable(); 
            $table->string('proyecto_asociado')->nullable(); 
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('empresas');
    }
};