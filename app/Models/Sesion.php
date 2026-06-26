<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sesion extends Model
{
    use SoftDeletes;
    protected $table = 'sesiones';

    protected $fillable = [
        'user_id',
        'microreto_id',
        'microreto_titulo',
        'fecha',
        'centro_educativo',
        'ciclo_formativo',
        'curso',
        'grupo',
        'num_alumnos',
        'notas',
    ];

    protected $casts = [
        'fecha'         => 'date:Y-m-d',
        'num_alumnos'   => 'integer',
        'microreto_id'  => 'integer',
    ];

    public function docente()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function microreto()
    {
        return $this->belongsTo(Microreto::class);
    }

    public function microproyectos()
    {
        return $this->hasMany(Microproyecto::class);
    }
}
