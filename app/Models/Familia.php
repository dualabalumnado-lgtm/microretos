<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Familia extends Model
{
    use SoftDeletes;
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
