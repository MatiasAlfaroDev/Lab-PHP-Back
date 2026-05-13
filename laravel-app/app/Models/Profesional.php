<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profesional extends Model
{
    protected $table = 'profesionales';

    // Usamos profesionalId como la clave primaria
    protected $primaryKey = 'profesionalId';

    // Como el ID viene de la tabla Users, le decimos a Laravel que no intente autoincrementarlo.
    public $incrementing = false;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'profesionalId',
        'descripcion',
        'ubicacion'
    ];

    //Relación inversa: Un profesional "es" un usuario. Vinculamos nuestro profesionalId con el id del User.
    public function user()
    {
        return $this->belongsTo(User::class, 'profesionalId', 'id');
    }

    //Un profesional tiene muchos servicios.
    public function servicios()
    {
        return $this->hasMany(Servicio::class, 'profesionalId');
    }
}