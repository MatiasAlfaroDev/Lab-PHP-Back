<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paquete extends Model
{
    protected $table = 'paquetes';
    protected $primaryKey = 'paqueteId';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'precioTotal',
        'descripcion'
    ];

    //Relación con los ítems: Un paquete se compone de varios servicios y sus cantidades.
    public function items()
    {
        return $this->hasMany(ItemPaquete::class, 'paqueteId');
    }

    //Acceso directo a los Servicios: Permite obtener todos los servicios del paquete sin pasar manualmente por los ítems.
    
    public function servicios()
    {
        return $this->belongsToMany(Servicio::class, 'item_paquetes', 'paqueteId', 'servicioId')
                    ->withPivot('cantidadSesiones');
    }

    /**
     * MÉTODO DE LÓGICA: Obtener el Profesional.
     * Como el paquete no tiene profesionalId, lo buscamos en el primer servicio que lo integra.
     */
    public function getProfesionalAttribute()
    {
        $primerItem = $this->items()->first();
        
        if ($primerItem && $primerItem->servicio) {
            return $primerItem->servicio->profesional;
        }

        return null;
    }
}