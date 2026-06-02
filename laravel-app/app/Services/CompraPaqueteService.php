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
            'items.itemPaquete',
            'pago'
        ])
        ->where('cliente_id', $user->id)
        ->get();
    }
}