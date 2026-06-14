<?php
namespace App\Services;
use App\Models\Excepcion;

class ExcepcionService
{
    public function listar($user): array
{
    $excepciones = Excepcion::where(
        'profesional_id',
        $user->id
    )
    ->orderBy('fecha_desde')
    ->get();

    return [
        'success' => true,
        'data' => $excepciones
    ];
}
   public function crear(array $data, $user): array
{
    Excepcion::create([
        'profesional_id' => $user->id,
        'fecha_desde' => $data['fecha_desde'],
        'fecha_hasta' => $data['fecha_hasta'] ?? $data['fecha_desde'],
        'hora_inicio' => $data['hora_inicio'] ?? null,
        'hora_fin' => $data['hora_fin'] ?? null,
        'motivo' => $data['motivo'] ?? null,
    ]);

    return [
        'success' => true,
        'message' => 'Excepción creada'
    ];
}

    public function eliminar(int $excepcionId, $user): array
    {
        $excepcion = Excepcion::find($excepcionId);

        if (!$excepcion) {
            return [
                'success' => false,
                'message' => 'Excepción no encontrada'
            ];
        }

        if ((int)$excepcion->profesional_id !== (int)$user->id) {
            return [
                'success' => false,
                'message' => 'No tenés permiso'
            ];
        }

        $excepcion->delete();

        return [
            'success' => true,
            'message' => 'Excepción eliminada'
        ];
    }
}