<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    const ROLE_ADMIN   = 1;
    const ROLE_DOCENTE = 2;
    const ROLE_EMPRESA = 3;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_blocked',
        'email_verified_at',
        'centro_educativo_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'role'              => 'integer',
            'is_blocked'        => 'boolean',
        ];
    }

    public function isAdmin(): bool   { return $this->role === self::ROLE_ADMIN; }
    public function isDocente(): bool { return $this->role === self::ROLE_DOCENTE; }
    public function isEmpresa(): bool { return $this->role === self::ROLE_EMPRESA; }

    public function roleName(): string
    {
        return match($this->role) {
            self::ROLE_DOCENTE => 'Docente',
            self::ROLE_EMPRESA => 'Empresa',
            default            => 'Administrador',
        };
    }

    // Una cuenta está operativa si está verificada y no bloqueada.
    // Los admins siempre son operativos (se gestionan desde el servidor).
    public function centroEducativo()
    {
        return $this->belongsTo(\App\Models\CentroEducativo::class, 'centro_educativo_id');
    }

    public function isOperational(): bool
    {
        if ($this->isAdmin()) return true;
        return !$this->is_blocked && $this->email_verified_at !== null;
    }
}
