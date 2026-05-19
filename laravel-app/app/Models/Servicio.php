<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Profesional; 

class Servicio extends Model
{
    protected $table = 'servicios';

    protected $primaryKey = 'servicio_id';

    public $timestamps = false;

    protected $fillable = [
        'profesional_id',
        'nombre',
        'descripcion',
        'tipo',
        'precio',
        'duracion',
        'pausa',
        'modalidad'
    ];

    // un servicio pertenece a un profesional
    public function profesional()
    {
        return $this->belongsTo(Profesional::class, 'profesional_id');
    }

    // un servicio tiene muchas disponibilidades
    public function disponibilidades()
    {
        return $this->hasMany(Disponibilidad::class, 'servicio_id');
    }

    // un servicio puede estar en muchos ítems de paquetes
    public function itemPaquetes()
    {
        return $this->hasMany(ItemPaquete::class, 'servicio_id');
    }

    public function reservas()
{
    return $this->hasMany(Reserva::class, 'servicio_id');
}
}