<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    use HasFactory;

    // Aquí le decimos a Laravel: "Te doy permiso para rellenar automáticamente todos estos campos"
    protected $fillable = [
        'cif', 
        'nombre_comercial', 
        'razon_social', 
        'telefono', 
        'email_general', 
        'estado_contacto', 
        'fecha_cita', 
        'persona_contacto', 
        'email_contacto', 
        'posicion_contacto', 
        'sector', 
        'actividad', 
        'horario_atencion', 
        'direccion', 
        'numero', 
        'otros_direccion', 
        'codigo_postal', 
        'municipio', 
        'provincia', 
        'web', 
        'proyecto_asociado',
        'centro_educativo', 
        
        
        'tamano',
        'dia_a_normal',
        'friccion_area',
        'friccion_problema',
        'consecuencias',
        'restricciones',
        'lo_que_no_quieren'
    ];
}