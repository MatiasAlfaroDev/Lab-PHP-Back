<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Calificacion extends Model
{
    protected $table = 'calificaciones';

    protected $primaryKey = 'calificacionId';
    public $timestamps = true;

    protected $fillable = [
        'reservaId',
        'puntaje',
        'comentario'
    ];

    //Una calificación pertenece a una reserva específica.
    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'reservaId');
    }
}