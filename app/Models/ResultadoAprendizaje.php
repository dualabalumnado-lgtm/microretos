<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResultadoAprendizaje extends Model
{
    protected $table = 'resultados_aprendizaje';

    // Un RA pertenece a un Módulo
    public function modulo()
    {
        return $this->belongsTo(Modulo::class, 'idmodulo');
    }

    // Un RA tiene muchos Criterios de Evaluación (CE)
    public function criteriosEvaluacion()
    {
        return $this->hasMany(CriterioEvaluacion::class, 'idmoduloRA');
    }
}