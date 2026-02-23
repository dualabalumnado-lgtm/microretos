<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CicloFormativo extends Model
{
    protected $table = 'dualnueva_ciclosformativos_angel';
    public $timestamps = false; 

    protected $fillable = [
        'idCiclo', 'nombre', 'familia', 'grado', 'referenciaBOE', 'siglasGrado'
    ];

    // Relación: Un Ciclo tiene muchos Módulos
    public function modulos()
    {
        return $this->hasMany(Modulo::class, 'idcicloformativo', 'id');
    }
}