<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Modulo extends Model
{
    protected $table = 'dualnueva_modulos_angel';
    public $timestamps = false;

    protected $fillable = [
        'idAreaSC', 'idcicloformativo', 'codigoBOE', 'nombre', 'curso', 'horastotales'
    ];

    // Relación inversa: Un Módulo pertenece a un Ciclo
    public function ciclo()
    {
        return $this->belongsTo(CicloFormativo::class, 'idcicloformativo', 'id');
    }

    // Relación: Un Módulo tiene muchos RA
    public function resultadosAprendizaje()
    {
        return $this->hasMany(ResultadoAprendizaje::class, 'idmodulo', 'id');
    }
}
