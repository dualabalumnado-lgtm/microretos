<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Microreto extends Model
{
    protected $fillable = [
        'titulo', 'empresa_nombre', 'quien_es', 'dia_a_dia', 'pregunta_reto',
        'dificultades', 'que_necesitan', 'limitaciones', 'prototipos',
        'ods_sugeridos', 'soft_skills', 'evaluacion_oficial', 'tips_profesorado',
        'nivel_grupo', 'ciclo', 'modulo', 'duracion', 'es_simulado'
    ];

    protected $casts = [
        'dificultades' => 'array',
        'que_necesitan' => 'array',
        'limitaciones' => 'array',
        'prototipos' => 'array',
        'ods_sugeridos' => 'array',
        'soft_skills' => 'array',
        'evaluacion_oficial' => 'array',
        'tips_profesorado' => 'array', 'es_simulado' => 'boolean',
    ];
}