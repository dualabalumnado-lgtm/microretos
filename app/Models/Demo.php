<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Demo extends Model
{
    protected $fillable = [
        'familia_profesional',
        'etiqueta',
        'empresa_nombre',
        'empresa_sector',
        'empresa_tamano',
        'empresa_web',
        'empresa_centro',
        'dia_a_normal',
        'friccion_area',
        'friccion_problema',
        'restricciones',
        'otra_limitacion',
        'lo_que_no_quieren',
        'consecuencias',
        'otra_consecuencia',
        'expectativas_alumno',
        'nivel_grupo',
        'curso_seleccionado',
        'duracion',
        'cantidad_microretos',
    ];

    protected $casts = [
        'restricciones'       => 'array',
        'consecuencias'       => 'array',
        'curso_seleccionado'  => 'integer',
        'cantidad_microretos' => 'integer',
    ];

    public function microretos()
    {
        return $this->hasMany(\App\Models\Microreto::class, 'demo_id');
    }
}