<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Excepcion extends Model
{
    protected $table = 'excepciones';

    protected $primaryKey = 'excepcion_id';
    public $timestamps = false;

    protected $fillable = [
        'profesional_id', 
        'fecha',      
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