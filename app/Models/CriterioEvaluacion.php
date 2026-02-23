<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CriterioEvaluacion extends Model
{
    protected $table = 'dualnueva_modulosRACE_angel';
    public $timestamps = false;

    protected $fillable = [
        'idmoduloRA', 'ce'
    ];

    // Relación inversa: Un CE pertenece a un RA
    public function resultadoAprendizaje()
    {
        return $this->belongsTo(ResultadoAprendizaje::class, 'idmoduloRA', 'id');
    }
}