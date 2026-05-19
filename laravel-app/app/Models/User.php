<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Database\Factories\UserFactory;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'nombre',
        'email',
        'password',
        'rol', // rol seria para 'admin' o 'usuario'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    //Un Usuario puede tener un perfil de Profesional.
    public function profesional()
    {
        return $this->hasOne(Profesional::class, 'profesional_id', 'id');
    }

    //Un Usuario puede tener un perfil de Cliente.
    public function cliente()
    {
        return $this->hasOne(Cliente::class, 'cliente_id', 'id');
    }

}