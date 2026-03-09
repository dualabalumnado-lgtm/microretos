<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Modulo extends Model
{
    protected $table = 'modulos';

    // Un Módulo pertenece a un Ciclo
    public function cicloFormativo()
    {
        return $this->belongsTo(CicloFormativo::class, 'idcicloformativo');
    }

    // Un Módulo tiene muchos Resultados de Aprendizaje (RA)
    public function ras()
    {
        return $this->hasMany(ResultadoAprendizaje::class, 'idmodulo');
    }
}