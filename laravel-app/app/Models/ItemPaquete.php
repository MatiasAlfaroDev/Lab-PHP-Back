<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemPaquete extends Model
{
    protected $table = 'item_paquetes';

    protected $primaryKey = 'item_paquete_id';
    public $timestamps = false;

    protected $fillable = [
        'servicio_id',
        'paquete_id',      
        'cantidad_sesiones'
    ];

    //Un item de paquete pertenece a un servicio.
    public function servicio()
    {
        return $this->belongsTo(Servicio::class, 'servicio_id');
    }

    //Un item de paquete pertenece a un paquete.
    public function paquete()
    {
        return $this->belongsTo(Paquete::class, 'paquete_id');
    }
}