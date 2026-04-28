<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sesion extends Model
{
    protected $table = 'sesiones';

    protected $fillable = [
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

    public function microreto()
    {
        return $this->belongsTo(Microreto::class);
    }

    public function microproyectos()
    {
        return $this->hasMany(Microproyecto::class);
    }
}
