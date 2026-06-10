<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'clientes';

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'user_id'
    ];

    // Un cliente es un usuario
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Un cliente puede tener muchas reservas
    public function reservas()
    {
        return $this->hasMany(Reserva::class, 'cliente_id');
    }
}