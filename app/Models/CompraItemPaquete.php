<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompraItemPaquete extends Model
{
    protected $table = 'compra_item_paquetes';

    protected $primaryKey = 'compra_item_paquete_id';

    public $timestamps = false;

    protected $fillable = [
        'compra_paquete_id',
        'item_paquete_id',
        'sesiones_restantes'
    ];

    // Pertenece a una compra de paquete
    public function compraPaquete()
    {
        return $this->belongsTo(CompraPaquete::class, 'compra_paquete_id');
    }

    // Pertenece a un item del paquete original
    public function itemPaquete()
    {
        return $this->belongsTo(ItemPaquete::class, 'item_paquete_id');
    }

    // Un saldo puede usarse en muchas reservas
    public function reservas()
    {
        return $this->hasMany(Reserva::class, 'compra_item_paquete_id');
    }
}