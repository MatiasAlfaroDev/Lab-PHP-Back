<?php

namespace App\Services;

use App\Models\Paquete;
use App\Models\CompraPaquete;
use App\Models\CompraItemPaquete;
use Illuminate\Support\Facades\DB;

class CompraPaqueteService
{
    public function comprarPaquete($user, $paquete_id)
    {
        if (!$user->cliente) {
                return [
                    'success' => false,
                    'message' => 'Solo los clientes pueden comprar paquetes'
                ];
        }

        DB::beginTransaction();

        try {

            $paquete = Paquete::with('items')
                ->findOrFail($paquete_id);

            $compra = CompraPaquete::create([
                'cliente_id' => $user->id,
                'paquete_id' => $paquete->paquete_id,
                'fecha_compra' => now()->toDateString(),
            ]);

            foreach ($paquete->items as $item) {

                CompraItemPaquete::create([
                    'compra_paquete_id' => $compra->compra_paquete_id,
                    'item_paquete_id' => $item->item_paquete_id,
                    'sesiones_restantes' => $item->cantidad_sesiones,
                ]);
            }

            DB::commit();

            return [
                'success' => true,
                'compra_paquete_id' => $compra->compra_paquete_id,
            ];

        } catch (\Exception $e) {

            DB::rollBack();

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function misPaquetes($user)
    {
        return CompraPaquete::with([
            'paquete',
            'items.itemPaquete.servicio',
            'pago'
        ])
        ->where('cliente_id', $user->id)
        ->get();
    }

    public function obtenerCompra($id, $user)
    {
        return CompraPaquete::with([
            'paquete',
            'items.itemPaquete.servicio',
            'pago'
        ])
        ->where('cliente_id', $user->id)
        ->where('compra_paquete_id', $id)
        ->first();
    }

    public function cancelarCompra($id, $user)
    {
        $compra = CompraPaquete::with([
            'items',
            'pago'
        ])
        ->where('cliente_id', $user->id)
        ->where('compra_paquete_id', $id)
        ->first();

        if (!$compra) {
            return [
                'success' => false,
                'message' => 'Compra no encontrada'
            ];
        }

        if (
            $compra->pago &&
            $compra->pago->estado === 'aprobado'
        ) {
            return [
                'success' => false,
                'message' => 'No se puede cancelar una compra pagada'
            ];
        }

        DB::transaction(function () use ($compra) {

            $compra->items()->delete();

            if ($compra->pago) {
                $compra->pago()->delete();
            }

            $compra->delete();
        });

        return [
            'success' => true,
            'message' => 'Compra cancelada correctamente'
        ];
    }
}