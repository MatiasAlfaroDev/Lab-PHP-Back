<?php

namespace App\Services;

use App\Models\Servicio;
use App\Models\Profesional;
use App\Services\GeocodingService;

class ServicioService
{
    public function __construct(private GeocodingService $geocodingService) {}

    private function resolverUbicacion(array $data, string $modalidad): array
    {
        $esPresencial = in_array($modalidad, ['presencial', 'hibrido']);

        if (!$esPresencial) {
            return ['direccion' => null, 'latitud' => null, 'longitud' => null];
        }

        $direccion = $data['direccion'] ?? null;
        $latitud   = $data['latitud']   ?? null;
        $longitud  = $data['longitud']  ?? null;

        // Si se da dirección sin coordenadas, geocodificar automáticamente
        if ($direccion && ($latitud === null || $longitud === null)) {
            $geo = $this->geocodingService->geocodificar($direccion);
            if ($geo) {
                return [
                    'direccion' => $geo['direccion_formateada'],
                    'latitud'   => $geo['latitud'],
                    'longitud'  => $geo['longitud'],
                ];
            }
        }

        return compact('direccion', 'latitud', 'longitud');
    }

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

        $modalidad = strtolower($data['modalidad']);
        $ubicacion = $this->resolverUbicacion($data, $modalidad);

        $servicio = Servicio::create([
            'profesional_id' => $profesional->user_id,
            'nombre'         => $data['nombre'],
            'descripcion'    => $data['descripcion'],
            'modalidad'      => $modalidad,
            'tipo'           => $data['tipo'],
            'precio'         => $data['precio'],
            'duracion'       => $data['duracion'],
            'pausa'          => $data['pausa'],
            'min_cancelacion'=> $data['min_cancelacion'] ?? 24,
            'direccion'      => $ubicacion['direccion'],
            'latitud'        => $ubicacion['latitud'],
            'longitud'       => $ubicacion['longitud'],
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

        $modalidad = isset($data['modalidad']) ? strtolower($data['modalidad']) : $servicio->modalidad;
        $ubicacion = $this->resolverUbicacion($data + [
            'direccion' => $servicio->direccion,
            'latitud'   => $servicio->latitud,
            'longitud'  => $servicio->longitud,
        ], $modalidad);

        $servicio->update([
            'nombre'          => $data['nombre']          ?? $servicio->nombre,
            'descripcion'     => $data['descripcion']     ?? $servicio->descripcion,
            'modalidad'       => $modalidad,
            'tipo'            => $data['tipo']             ?? $servicio->tipo,
            'precio'          => $data['precio']           ?? $servicio->precio,
            'duracion'        => $data['duracion']         ?? $servicio->duracion,
            'pausa'           => $data['pausa']            ?? $servicio->pausa,
            'min_cancelacion' => $data['min_cancelacion']  ?? $servicio->min_cancelacion,
            'direccion'       => $ubicacion['direccion'],
            'latitud'         => $ubicacion['latitud'],
            'longitud'        => $ubicacion['longitud'],
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
        )
        ->withCount('reservas')
        ->get();

        return [
            'success' => true,
            'data' => $servicios
        ];
    }
}