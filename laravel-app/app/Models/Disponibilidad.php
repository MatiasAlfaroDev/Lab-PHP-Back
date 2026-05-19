<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Disponibilidad extends Model
{
    protected $table = 'disponibilidades';

    protected $primaryKey = 'disponibilidad_id';
    public $timestamps = false;

    protected $fillable = [
        'servicio_id',
        'dia_semana', 
        'hora_inicio',
        'hora_fin'
    ];

    // Una disponibilidad pertenece a un único servicio.
    public function servicio()
    {
        return $this->belongsTo(Servicio::class, 'servicio_id');
    }
}