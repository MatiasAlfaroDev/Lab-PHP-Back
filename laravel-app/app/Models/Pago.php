<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $table = 'pagos';

    protected $primaryKey = 'pago_id';

    public $timestamps = false;//?

    protected $fillable = [
        'reserva_id',
        'compra_paquete_id',
        'fecha',
        'monto',
        'estado'
    ];

    //Un pago pertenece a una reserva.
    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }

    //Un pago pertenece a una compra de paquete.
    public function compraPaquete()
    {
        return $this->belongsTo(CompraPaquete::class, 'compra_paquete_id');
    }
}