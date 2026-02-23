<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResultadoAprendizaje extends Model
{
    protected $table = 'dualnueva_modulosRA_angel';
    public $timestamps = false;

    protected $fillable = [
        'idmodulo', 'ra'
    ];

    // Relación inversa: Un RA pertenece a un Módulo
    public function modulo()
    {
        return $this->belongsTo(Modulo::class, 'idmodulo', 'id');
    }

    // Relación: Un RA tiene muchos CE
    public function criteriosEvaluacion()
    {
        return $this->hasMany(CriterioEvaluacion::class, 'idmoduloRA', 'id');
    }
}