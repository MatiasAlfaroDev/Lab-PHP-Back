<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Profesional; 

class Servicio extends Model
{
    protected $table = 'servicios';

    protected $primaryKey = 'servicioId';

    public $timestamps = false;

    protected $fillable = [
        'profesionalId',
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
        return $this->belongsTo(Profesional::class, 'profesionalId');
    }

    // un servicio tiene muchas disponibilidades
    public function disponibilidades()
    {
        return $this->hasMany(Disponibilidad::class, 'servicioId');
    }

    // un servicio puede estar en muchos ítems de paquetes
    public function itemPaquetes()
    {
        return $this->hasMany(ItemPaquete::class, 'servicioId');
    }

    public function reservas()
{
    return $this->hasMany(Reserva::class, 'servicioId');
}
}