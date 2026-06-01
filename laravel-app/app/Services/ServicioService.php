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
            'pausa' => $data['pausa'],
            'min_cancelacion' => $data['min_cancelacion'] ?? 24,
        ]);

        return [
            'success' => true,
            'message' => 'Servicio creado correctamente',
            'data' => $servicio
        ];
    }

    public function actualizarServicio(int $id, array $data, $user)
    {
        $servicio = Servicio::findOrFail($id);
        $profesional = Profesional::where('user_id', $user->id)->first();

        if (!$profesional || $servicio->profesional_id !== $profesional->user_id) {
            return ['success' => false, 'message' => 'No tenés permiso para editar este servicio'];
        }

        $servicio->update([
            'nombre'          => $data['nombre']          ?? $servicio->nombre,
            'descripcion'     => $data['descripcion']     ?? $servicio->descripcion,
            'modalidad'       => isset($data['modalidad']) ? strtolower($data['modalidad']) : $servicio->modalidad,
            'tipo'            => $data['tipo']             ?? $servicio->tipo,
            'precio'          => $data['precio']           ?? $servicio->precio,
            'duracion'        => $data['duracion']         ?? $servicio->duracion,
            'pausa'           => $data['pausa']            ?? $servicio->pausa,
            'min_cancelacion' => $data['min_cancelacion']  ?? $servicio->min_cancelacion,
        ]);

        return ['success' => true, 'message' => 'Servicio actualizado', 'data' => $servicio->fresh()];
    }

    public function eliminarServicio(int $id, $user)
    {
        $servicio = Servicio::findOrFail($id);
        $profesional = Profesional::where('user_id', $user->id)->first();

        if (!$profesional || $servicio->profesional_id !== $profesional->user_id) {
            return ['success' => false, 'message' => 'No tenés permiso para eliminar este servicio'];
        }

        $servicio->delete();

        return ['success' => true, 'message' => 'Servicio eliminado'];
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