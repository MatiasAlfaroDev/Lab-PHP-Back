<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $table = 'pagos';

    protected $primaryKey = 'pagoId';

    public $timestamps = false;//?

    protected $fillable = [
        'reservaId',
        'fecha',
        'monto',
        'estado'
    ];

    //Un pago pertenece a una reserva.
    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'reservaId');
    }
}