<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MicroretoToken extends Model
{
    protected $fillable = ['microreto_id', 'token', 'expires_at'];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function microreto()
    {
        return $this->belongsTo(Microreto::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
