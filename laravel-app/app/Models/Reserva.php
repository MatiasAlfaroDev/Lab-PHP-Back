<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reserva extends Model
{
    protected $table = 'reservas';

    protected $primaryKey = 'reserva_id';

    public $timestamps = false;

    protected $fillable = [
        'cliente_id',
        'servicio_id',
        'compra_item_paquete_id',
        'fecha',
        'hora',
        'estado',
        'modalidad',
        'estado_videollamada'
    ];

    // Cliente dueño de la reserva
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    // Servicio reservado
    public function servicio()
    {
        return $this->belongsTo(Servicio::class, 'servicio_id');
    }

    // Si viene de un paquete (nullable)
    public function compraItemPaquete()
    {
        return $this->belongsTo(CompraItemPaquete::class, 'compra_item_paquete_id');
    }

    // Pago de la reserva
    public function pago()
    {
        return $this->hasOne(Pago::class, 'reserva_id');
    }

    // Calificación
    public function calificacion()
    {
        return $this->hasOne(Calificacion::class, 'reserva_id');
    }
}