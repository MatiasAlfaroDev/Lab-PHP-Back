<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemPaquete extends Model
{
    protected $table = 'item_paquetes';

    protected $primaryKey = 'itemPaqueteId';
    public $timestamps = false;

    protected $fillable = [
        'servicioId',
        'paqueteId',      
        'cantidadSesiones'
    ];

    //Un item de paquete pertenece a un servicio.
    public function servicio()
    {
        return $this->belongsTo(Servicio::class, 'servicioId');
    }

    //Un item de paquete pertenece a un paquete.
    public function paquete()
    {
        return $this->belongsTo(Paquete::class, 'paqueteId');
    }
}