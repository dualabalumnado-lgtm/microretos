<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CicloFormativo extends Model
{
    protected $table = 'ciclos_formativos';

    // Un Ciclo tiene muchos Módulos
    public function modulos()
    {
        return $this->hasMany(Modulo::class, 'idcicloformativo');
    }
}