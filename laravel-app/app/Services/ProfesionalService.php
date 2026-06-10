<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;

class ProfesionalService
{
    public function updateProfile($user, array $data)
    {
        DB::beginTransaction();

        try {
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
            ]);

            if ($user->profesional) {
                $user->profesional->update([
                    'descripcion' => $data['descripcion'] ?? '',
                ]);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Perfil actualizado correctamente',
                'data' => $user->load('profesional')
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Error al actualizar perfil'
            ];
        }
    }

    public function reprogramar(int $reservaId, string $fecha, string $hora): array
    {
        return DB::transaction(function () use ($reservaId, $fecha, $hora) {

            $reserva = Reserva::findOrFail($reservaId);

            // solo se puede reprogramar si está confirmada o pagada
            if (!in_array($reserva->estado, ['confirmada', 'pagada'])) {
                return [
                    'success' => false,
                    'message' => 'Solo se pueden reprogramar reservas confirmadas o pagadas'
                ];
            }

            $reserva->update([
                'fecha' => $fecha,
                'hora' => $hora 
            ]);

            return [
                'success' => true,
                'message' => 'Reserva reprogramada correctamente',
                'data' => $reserva
            ];
        });
    }
}