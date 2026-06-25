<?php

namespace App\Services;

use App\Events\CatalogoActualizado;
use App\Models\Servicio;
use App\Models\Profesional;
use App\Models\Reserva;
use App\Models\ItemPaquete;
use App\Models\User;
use App\Models\Calificacion;
use App\Services\GeocodingService;
use Illuminate\Support\Facades\DB;

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

    public function listarTodos(array $filtros = [])
    {
        $query = Servicio::with('profesional.user')
            ->whereRaw('eliminado = false')
            ->whereHas('profesional.user', function ($query) {
                $query->whereRaw('activo = true');
            });

        if (!empty($filtros['q'])) {
            $texto = $filtros['q'];
            $query->where(function ($query) use ($texto) {
                $query->where('nombre', 'like', "%{$texto}%")
                      ->orWhere('descripcion', 'like', "%{$texto}%");
            });
        }

        if (!empty($filtros['tipo'])) {
            $query->where('tipo', $filtros['tipo']);
        }

        if (!empty($filtros['modalidad'])) {
            $query->where('modalidad', $filtros['modalidad']);
        }

        if (is_numeric($filtros['precio_min'] ?? null)) {
            $query->where('precio', '>=', $filtros['precio_min']);
        }

        if (is_numeric($filtros['precio_max'] ?? null)) {
            $query->where('precio', '<=', $filtros['precio_max']);
        }

        if (
            is_numeric($filtros['lat'] ?? null) &&
            is_numeric($filtros['lng'] ?? null) &&
            is_numeric($filtros['radio_km'] ?? null)
        ) {
            $lat = $filtros['lat'];
            $lng = $filtros['lng'];
            $haversine = "(6371 * acos(cos(radians($lat)) * cos(radians(latitud)) "
                . "* cos(radians(longitud) - radians($lng)) + sin(radians($lat)) * sin(radians(latitud))))";

            $query->whereNotNull('latitud')
                  ->whereNotNull('longitud')
                  ->whereRaw("$haversine <= ?", [$filtros['radio_km']]);
        }

        if (($filtros['orden'] ?? null) === 'precio_asc') {
            $query->orderBy('precio', 'asc');
        } elseif (($filtros['orden'] ?? null) === 'precio_desc') {
            $query->orderBy('precio', 'desc');
        }

        $calcularRating = function ($servicio) {
            $stats = Calificacion::whereHas('reserva', function ($q) use ($servicio) {
                $q->where('servicio_id', $servicio->servicio_id);
            })
            ->selectRaw('AVG(puntuacion) as promedio, COUNT(*) as cantidad')
            ->first();

            $servicio->promedio = round($stats->promedio ?? 0, 1);
            $servicio->cantidad_calificaciones = $stats->cantidad ?? 0;

            return $servicio;
        };

        // Sin "page"/"per_page" se mantiene el comportamiento original (lista completa, sin paginar).
        if (isset($filtros['page']) || isset($filtros['per_page'])) {
            $servicios = $query->paginate((int) ($filtros['per_page'] ?? 20));
            $servicios->getCollection()->transform($calcularRating);
        } else {
            $servicios = $query->get()->map($calcularRating);
        }

        return [
            'success' => true,
            'data' => $servicios,
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
        $tipo = trim($data['tipo']);
        $tipoExistente = Servicio::whereRaw(
            'LOWER(tipo) = ?',
            [mb_strtolower($tipo)]
        )->first();

        if ($tipoExistente) {
            $tipo = $tipoExistente->tipo;
        }

        $servicio = Servicio::create([
            'profesional_id' => $profesional->user_id,
            'nombre'         => $data['nombre'],
            'descripcion'    => $data['descripcion'],
            'modalidad'      => $modalidad,
            'tipo'           => $tipo,
            'precio'         => $data['precio'],
            'duracion'       => $data['duracion'],
            'pausa'          => $data['pausa'],
            'min_cancelacion'=> $data['min_cancelacion'] ?? 24,
            'direccion'      => $ubicacion['direccion'],
            'latitud'        => $ubicacion['latitud'],
            'longitud'       => $ubicacion['longitud'],
        ]);

        event(new CatalogoActualizado('servicio', 'creado', $profesional->user_id, $servicio->servicio_id));

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

        // Si cambia la dirección sin coordenadas explícitas → re-geocodificar
        // Si la dirección no cambia → conservar coordenadas existentes
        $direccionCambiada = array_key_exists('direccion', $data) && $data['direccion'] !== $servicio->direccion;
        $dataUbicacion = $data;
        if (!$direccionCambiada) {
            $dataUbicacion['direccion'] ??= $servicio->direccion;
            $dataUbicacion['latitud']   ??= $servicio->latitud;
            $dataUbicacion['longitud']  ??= $servicio->longitud;
        }

        $ubicacion = $this->resolverUbicacion($dataUbicacion, $modalidad);

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

        event(new CatalogoActualizado('servicio', 'actualizado', $servicio->profesional_id, $servicio->servicio_id));

        return ['success' => true, 'message' => 'Servicio actualizado', 'data' => $servicio->fresh()];
    }

    public function eliminarServicio(int $id, $user)
    {
        $servicio = Servicio::findOrFail($id);
        $profesional = Profesional::where('user_id', $user->id)->first();

        if (!$profesional || $servicio->profesional_id !== $profesional->user_id) {
            return [
                'success' => false,
                'message' => 'No tenés permiso para eliminar este servicio'
            ];
        }

        $tieneReservas = Reserva::where('servicio_id', $id)
            ->whereIn('estado', [
                'pendiente',
                'confirmada',
                'pagada'
            ])
            ->where('fecha', '>=', now()->toDateString())
            ->exists();

        if ($tieneReservas) {
            return [
                'success' => false,
                'message' => 'No se puede eliminar el servicio porque tiene reservas futuras.'
            ];
        }

        $estaEnPaquete = ItemPaquete::where('servicio_id', $id)->exists();

        if ($estaEnPaquete) {
            return [
                'success' => false,
                'message' => 'No se puede eliminar el servicio porque pertenece a un paquete.'
            ];
        }

        DB::table('servicios')
        ->where('servicio_id', $id)
        ->update([
            'eliminado' => DB::raw('true')
        ]);

        event(new CatalogoActualizado('servicio', 'eliminado', $servicio->profesional_id, $servicio->servicio_id));

        return [
            'success' => true,
            'message' => 'Servicio eliminado correctamente'
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
        )
        ->whereRaw('eliminado = false')
        ->withCount('reservas')
        ->get();

        return [
            'success' => true,
            'data' => $servicios
        ];
    }
}