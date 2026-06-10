<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Database\Factories\UserFactory;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

     protected $table = 'users';

     protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role', // rol seria para 'admin' o 'usuario'
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
        return $this->hasOne(Profesional::class, 'user_id');
    }

    //Un Usuario puede tener un perfil de Cliente.
    public function cliente()
    {
        return $this->hasOne(Cliente::class, 'user_id');
    }

    public function receivesBroadcastNotificationsOn(): string
    {
    return 'user.' . $this->id;
    }

}