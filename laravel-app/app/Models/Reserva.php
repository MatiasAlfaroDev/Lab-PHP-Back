<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reserva extends Model
{
    protected $table = 'reservas';

    protected $primaryKey = 'reservaId';

    public $timestamps = false;

    protected $fillable = [
        'clienteId',
        'servicioId',
        'paqueteId',
        'fecha',
        'hora',
        'estado',
        'sesionesRestantes' //?
    ];

    // una reserva pertenece a un cliente
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'clienteId');
    }

    // una reserva tiene un servicio
    public function servicio()
    {
        return $this->belongsTo(Servicio::class, 'servicioId');
    }

    // una reserva puede ser de un paquete
    public function paquete()
    {
        return $this->belongsTo(Paquete::class, 'paqueteId');
    }

    // una reserva tiene un pago
    public function pago()
    {
        return $this->hasOne(Pago::class, 'reservaId');
    }

    // una reserva puede tener una calificación
    public function calificacion()
    {
        return $this->hasOne(Calificacion::class, 'reservaId');
    }
}