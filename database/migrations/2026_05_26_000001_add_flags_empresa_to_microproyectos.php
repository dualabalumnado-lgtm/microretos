<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('microproyectos', function (Blueprint $table) {
            // La empresa respondió "No validar aún" — proyecto sigue en filtro Propuesta
            // pero se muestra la etiqueta "Empresa contestó 'No validar aún'. Revisar"
            $table->boolean('empresa_no_valida_aun')
                  ->default(false)
                  ->after('empresa_validado');

            // El docente confirmó el envío del correo a la empresa desde la plataforma
            // Se muestra la etiqueta "Enviado a empresa por mail. Revisar"
            $table->boolean('enviado_a_empresa_mail')
                  ->default(false)
                  ->after('empresa_no_valida_aun');
        });
    }

    public function down(): void
    {
        Schema::table('microproyectos', function (Blueprint $table) {
            $table->dropColumn(['empresa_no_valida_aun', 'enviado_a_empresa_mail']);
        });
    }
};
