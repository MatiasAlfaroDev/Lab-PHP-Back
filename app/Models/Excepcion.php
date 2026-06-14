<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Excepcion extends Model
{
    protected $table = 'excepciones';

    protected $primaryKey = 'excepcion_id';
    
    protected $fillable = [
    'profesional_id',
    'fecha_desde',
    'fecha_hasta',
    'hora_inicio',
    'hora_fin',
    'motivo'
];

    //Una excepción pertenece a un profesional.
    public function profesional()
    {
        return $this->belongsTo(Profesional::class, 'profesional_id');
    }
}