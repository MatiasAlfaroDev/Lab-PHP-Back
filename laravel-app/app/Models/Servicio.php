<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Profesional; 

class Servicio extends Model
{
    protected $table = 'servicios';

    protected $primaryKey = 'servicio_id';

    public $timestamps = false;

    protected $fillable = [
        'profesional_id',
        'nombre',
        'descripcion',
        'tipo',
        'precio',
        'duracion',
        'pausa',
        'modalidad',
        'min_cancelacion',
        'min_aviso',
        'max_anticipacion_dias',
        'aceptar_automaticamente',
        'permitir_feriados',
    ];

    protected $casts = [
        'aceptar_automaticamente' => 'boolean',
        'permitir_feriados'       => 'boolean',
    ];

    // un servicio pertenece a un profesional
    public function profesional()
    {
        return $this->belongsTo(Profesional::class, 'profesional_id');
    }

    // un servicio tiene muchas disponibilidades
    public function disponibilidades()
    {
        return $this->hasMany(Disponibilidad::class, 'servicio_id');
    }

    // un servicio puede estar en muchos ítems de paquetes
    public function itemPaquetes()
    {
        return $this->hasMany(ItemPaquete::class, 'servicio_id');
    }

    public function reservas()
    {
        return $this->hasMany(Reserva::class, 'servicio_id');
    }

    public function paquetes() // un servicio puede pertenecer a muchos paquetes a través de item_paquetes, asi se accede directo de servicio a paquete sin pasar por item_paquete
    {
        return $this->belongsToMany(
            Paquete::class,
            'item_paquetes',
            'servicio_id',
            'paquete_id'
        )->withPivot('cantidad_sesiones');
    }
}