<?php

namespace App\Services;

use App\Models\Servicio;
use App\Models\Profesional;

class ServicioService
{
    public function listarTodos()
    {
        return [
            'success' => true,
            'data' => Servicio::with('profesional')->get()
        ];
    }

    public function nuevoServicio(array $data, $user)
    {
        // Verificar que el usuario sea professional
        if ($user->role !== 'professional') {
            return [
                'success' => false,
                'message' => 'Solo los profesionales pueden crear servicios'
            ];
        }

        // Buscar perfil profesional
        $profesional = Profesional::where('user_id', $user->id)->firstOrFail();
        
        if (!$profesional) {
            return [
                'success' => false,
                'message' => 'El usuario no tiene perfil profesional'
            ];
        }

        // Crear servicio
        $servicio = Servicio::create([
            'profesional_id' => $profesional->user_id,
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'],
            'modalidad' => strtolower($data['modalidad']),
            'tipo' => $data['tipo'],
            'precio' => $data['precio'],
            'duracion' => $data['duracion'],
            'pausa' => $data['pausa']
        ]);

        return [
            'success' => true,
            'message' => 'Servicio creado correctamente',
            'data' => $servicio
        ];
    }

    public function obtenerServiciosProfesional($user)
    {
        if ($user->role !== 'professional') {
            return [
                'success' => false,
                'message' => 'Solo los profesionales pueden ver sus servicios'
            ];
        }

        $profesional = Profesional::where('user_id', $user->id)->first();

        if (!$profesional) {
            return [
                'success' => false,
                'message' => 'Perfil profesional no encontrado'
            ];
        }

        $servicios = Servicio::where(
            'profesional_id',
            $profesional->user_id
        )->get();

        return [
            'success' => true,
            'data' => $servicios
        ];
    }
}