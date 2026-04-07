<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CentroEducativo extends Model
{
    protected $table    = 'centro_educativo';
    protected $fillable = ['nombre'];

    public function empresas()
    {
        return $this->hasMany(Empresa::class, 'centro_id');
    }

    public function ciclos()
    {
        return $this->belongsToMany(CicloFormativo::class, 'centro_ciclo', 'centro_id', 'ciclo_id');
    }
}
