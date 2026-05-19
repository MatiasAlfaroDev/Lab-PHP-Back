<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Calificacion extends Model
{
    protected $table = 'calificaciones';

    protected $primaryKey = 'calificacion_id';
    public $timestamps = true;

    protected $fillable = [
        'reserva_id',
        'puntuacion',
        'comentario'
    ];

    //Una calificación pertenece a una reserva específica.
    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }
}