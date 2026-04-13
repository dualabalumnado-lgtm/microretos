<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Microreto extends Model
{
    protected $fillable = [
        'titulo',
        'demo_id',          // FK → demos (para microretos de demo)
        'empresa_id',       // FK (nueva)
        'empresa_nombre',   // legacy — se mantiene hasta completar backfill
        'quien_es', 'dia_a_dia', 'pregunta_reto',
        'dificultades', 'que_necesitan', 'limitaciones', 'prototipos',
        'ods_sugeridos', 'soft_skills', 'evaluacion_oficial', 'tips_profesorado',
        'nivel_grupo',
        'curso',
        'ciclo_id',         // FK (nueva)
        'ciclo',            // legacy — se mantiene hasta completar backfill
        'modulo', 'duracion', 'es_simulado',
    ];

    protected $casts = [
        'dificultades'       => 'array',
        'que_necesitan'      => 'array',
        'limitaciones'       => 'array',
        'prototipos'         => 'array',
        'ods_sugeridos'      => 'array',
        'soft_skills'        => 'array',
        'evaluacion_oficial' => 'array',
        'tips_profesorado'   => 'array',
        'es_simulado'        => 'boolean',
    ];

    public function demo()
    {
        return $this->belongsTo(Demo::class, 'demo_id');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function cicloFormativo()
    {
        return $this->belongsTo(CicloFormativo::class, 'ciclo_id');
    }
}
