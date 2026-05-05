<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MicroproyectoRecurso extends Model
{
    protected $table = 'microproyecto_recursos';

    protected $fillable = [
        'microproyecto_id', 'tipo', 'label', 'filename',
        'url', 'public_id', 'resource_type', 'mime', 'size',
    ];

    public function microproyecto()
    {
        return $this->belongsTo(Microproyecto::class);
    }
}
