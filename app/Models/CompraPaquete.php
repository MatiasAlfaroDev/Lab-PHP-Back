<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompraPaquete extends Model
{
    protected $table = 'compra_paquetes';

    protected $primaryKey = 'compra_paquete_id';

    public $timestamps = false;

    protected $fillable = [
        'cliente_id',
        'paquete_id',
        'fecha_compra'
    ];

    // Una compra pertenece a un cliente
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    // Una compra corresponde a un paquete
    public function paquete()
    {
        return $this->belongsTo(Paquete::class, 'paquete_id');
    }

    // Una compra tiene muchos items/saldos por servicio
    public function items()
    {
        return $this->hasMany(CompraItemPaquete::class, 'compra_paquete_id');
    }

    // Una compra de paquete puede tener un pago asociado
    public function pago()
    {
        return $this->hasOne(Pago::class, 'compra_paquete_id');
    }

}