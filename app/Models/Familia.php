<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Familia extends Model
{
    protected $table    = 'familias';
    protected $fillable = ['nombre', 'imagen_url'];

    public function empresas()
    {
        return $this->belongsToMany(Empresa::class, 'empresa_familia', 'familia_id', 'empresa_id');
    }

    public function ciclos()
    {
        return $this->hasMany(CicloFormativo::class, 'familia_id');
    }
}
