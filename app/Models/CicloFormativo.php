<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CicloFormativo extends Model
{
    protected $table    = 'ciclos_formativos';
    protected $fillable = ['idCiclo', 'nombre', 'familia', 'familia_id', 'grado', 'referenciaBOE', 'siglasGrado'];

    public function familia()
    {
        return $this->belongsTo(Familia::class, 'familia_id');
    }

    public function modulos()
    {
        return $this->hasMany(Modulo::class, 'idcicloformativo');
    }

    public function centros()
    {
        return $this->belongsToMany(CentroEducativo::class, 'centro_ciclo', 'ciclo_id', 'centro_id');
    }

    public function microretos()
    {
        return $this->hasMany(Microreto::class, 'ciclo_id');
    }
}
