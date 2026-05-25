<?php

namespace App\Services;

use App\Models\Paquete;
use App\Models\Servicio;
use App\Models\ItemPaquete;
use Illuminate\Support\Facades\DB;

class PaqueteService
{
    public function crearPaquete(array $data)
    {
        DB::beginTransaction();

        try {
            // Crear el paquete
            $paquete = Paquete::create([
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'],
                'precio_total' => 0
            ]);

            $total = 0;

            foreach ($data['servicios'] as $item) {

                $servicio = Servicio::find($item['servicio_id']);

                if (!$servicio) {
                    throw new \Exception(
                        'Servicio con ID ' . $item['servicio_id'] . ' no encontrado'
                    );
                }

                ItemPaquete::create([
                    'paquete_id' => $paquete->paquete_id,
                    'servicio_id' => $item['servicio_id'],
                    'cantidad_sesiones' => $item['cantidad_sesiones']
                ]);

                $total += $servicio->precio * $item['cantidad_sesiones'];
            }

            // Actualizar el precio total del paquete
            $paquete->precio_total = $total;
            $paquete->save();

            DB::commit();

            return [
                'success' => true,
                'message' => 'Paquete creado correctamente',
                'paquete_id' => $paquete->paquete_id
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Error al crear el paquete: ' . $e->getMessage()
            ];
        }

    }

    public function listarPaquetes()
    {
        return Paquete::with('servicios')->get();
    }

    public function obtenerPaquete($id)
    {
        return Paquete::with('servicios')
            ->find($id);
    }

    public function eliminarPaquete($id)
    {
        $paquete = Paquete::find($id);

        if (!$paquete) {
            return [
                'success' => false,
                'message' => 'Paquete no encontrado'
            ];
        }

        $paquete->delete();

        return [
            'success' => true,
            'message' => 'Paquete eliminado correctamente'
        ];
    }
}