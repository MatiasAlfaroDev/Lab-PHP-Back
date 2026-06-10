<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paquete extends Model
{
    protected $table = 'paquetes';
    protected $primaryKey = 'paquete_id';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'precio_total',
        'descripcion'
    ];

    //Relación con los ítems: Un paquete se compone de varios servicios y sus cantidades.
    public function items()
    {
        return $this->hasMany(ItemPaquete::class, 'paquete_id');
    }

    //Acceso directo a los Servicios: Permite obtener todos los servicios del paquete sin pasar manualmente por los ítems.
    
    public function servicios()
    {
        return $this->belongsToMany(Servicio::class, 'item_paquetes', 'paquete_id', 'servicio_id')
                    ->withPivot('cantidad_sesiones');
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