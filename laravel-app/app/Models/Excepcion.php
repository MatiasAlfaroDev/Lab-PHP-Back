<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Excepcion extends Model
{
    protected $table = 'excepciones';

    protected $primaryKey = 'excepcionId';
    public $timestamps = false;

    protected $fillable = [
        'profesionalId',
        'fecha',      
        'horaInicio', 
        'horaFin',    
        'motivo' 
    ];

    //Una excepción pertenece a un profesional.
    public function profesional()
    {
        return $this->belongsTo(Profesional::class, 'profesionalId');
    }
}