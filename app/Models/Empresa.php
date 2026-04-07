<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    use HasFactory;

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
        'centro_educativo',  // legacy — se mantiene hasta completar backfill
        'centro_id',         // FK (nueva)
        'tamano',
        'dia_a_normal',
        'friccion_area',
        'friccion_problema',
        'consecuencias',
        'restricciones',
        'lo_que_no_quieren',
    ];

    public function centroEducativo()
    {
        return $this->belongsTo(CentroEducativo::class, 'centro_id');
    }

    public function familias()
    {
        return $this->belongsToMany(Familia::class, 'empresa_familia', 'empresa_id', 'familia_id');
    }

    public function microretos()
    {
        return $this->hasMany(Microreto::class, 'empresa_id');
    }
}
