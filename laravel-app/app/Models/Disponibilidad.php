<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Disponibilidad extends Model
{
    protected $table = 'disponibilidades';

    protected $primaryKey = 'disponibilidadId';
    public $timestamps = false;

    protected $fillable = [
        'servicioId',
        'diaSemana', 
        'horaInicio',
        'horaFin'
    ];

    // Una disponibilidad pertenece a un único servicio.
    public function servicio()
    {
        return $this->belongsTo(Servicio::class, 'servicioId');
    }
}