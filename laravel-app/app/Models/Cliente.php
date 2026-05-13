<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'clientes';

    // Definimos clienteId como la clave primaria
    protected $primaryKey = 'clienteId';

    // Como el ID se hereda del usuario creado previamente, desactivamos el autoincremento.
    public $incrementing = false;
    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'clienteId', // Recibirá el id del User
        'telefono',
        'direccion'
    ];

    //Relación inversa: Un Cliente "es" un Usuario.
    // Vincula clienteId con el id de la tabla users.
    public function user()
    {
        return $this->belongsTo(User::class, 'clienteId', 'id');
    }

    //Un cliente puede realizar muchas reservas.
    public function reservas()
    {
        return $this->hasMany(Reserva::class, 'clienteId');
    }
}