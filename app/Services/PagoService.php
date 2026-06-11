<?php

namespace App\Services;

use App\Models\Pago;
use App\Models\Reserva;
use App\Models\CompraPaquete;
use Illuminate\Support\Facades\DB;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use Carbon\Carbon;

class PagoService
{
    // PAYPAL CLIENT
    private function paypal(): PayPalClient
    {
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $provider->getAccessToken();
        return $provider;
    }

    // RESERVAS - INICIAR PAYPAL
    public function iniciarReservaPaypal($user, $reserva_id)
    {
        $reserva = Reserva::with('servicio', 'pago')
            ->findOrFail($reserva_id);

        if ((int) $reserva->cliente_id !== (int) $user->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        if ($reserva->pago?->estado === 'aprobado') {
            return response()->json(['message' => 'La reserva ya fue pagada'], 409);
        }

        $monto = number_format($reserva->servicio->precio, 2, '.', '');

        $paypal = $this->paypal();

        $order = $paypal->createOrder([
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'description' => 'Reserva: ' . $reserva->servicio->nombre,
                'amount' => [
                    'currency_code' => 'USD',
                    'value' => $monto,
                ],
            ]],
            'application_context' => [
                'return_url' => config('app.url') . '/api/pagos/reserva/capturar',
                'cancel_url' => config('app.url') . '/api/pagos/cancelar',
            ],
        ]);

        if (isset($order['error'])) {
            return response()->json([
                'message' => 'Error PayPal',
                'detail' => $order['error'],
            ], 500);
        }

        Pago::updateOrCreate(
            ['reserva_id' => $reserva->reserva_id],
            [
                'fecha' => now()->toDateString(),
                'monto' => $monto,
                'estado' => 'pendiente',
                'metodo' => 'paypal',
                'paypal_order_id' => $order['id'],
            ]
        );

        $approvalUrl = collect($order['links'])
            ->firstWhere('rel', 'approve')['href'];

        return response()->json([
            'approval_url' => $approvalUrl,
            'paypal_order_id' => $order['id'],
        ]);
    }

    // RESERVAS - CAPTURAR PAYPAL
    public function capturarReservaPaypal($orderId)
    {
        $pago = Pago::where(
            'paypal_order_id',
            $orderId
        )->firstOrFail();

        $paypal = $this->paypal();

        $result = $paypal->capturePaymentOrder($orderId);

        if (
            isset($result['error']) ||
            ($result['status'] ?? '') !== 'COMPLETED'
        ) {
            $pago->update([
                'estado' => 'fallido'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Pago fallido'
            ], 400);
        }

        $captureId =
            $result['purchase_units'][0]
            ['payments']['captures'][0]['id']
            ?? null;

        DB::transaction(function () use (
            $pago,
            $captureId
        ) {

            $pago->update([
                'estado' => 'aprobado',
                'paypal_capture_id' => $captureId,
                'fecha' => now()->toDateString(),
            ]);

            Reserva::where(
                'reserva_id',
                $pago->reserva_id
            )->update([
                'estado' => 'pagada'
            ]);
        });

        return response()->json([
            'success' => true
        ]);
    }

    // RESERVAS - SDK
    public function capturarReservaSDK(
        $reserva_id,
        $orderId
    ) {
        $pago = Pago::where('reserva_id', $reserva_id)
            ->where('paypal_order_id', $orderId)
            ->firstOrFail();

        $paypal = $this->paypal();

        $result = $paypal->capturePaymentOrder($orderId);

        if (
            isset($result['error']) ||
            ($result['status'] ?? '') !== 'COMPLETED'
        ) {
            $pago->update([
                'estado' => 'fallido'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Pago fallido'
            ], 400);
        }

        $captureId =
            $result['purchase_units'][0]
            ['payments']['captures'][0]['id']
            ?? null;

        DB::transaction(function () use (
            $pago,
            $captureId
        ) {

            $pago->update([
                'estado' => 'aprobado',
                'paypal_capture_id' => $captureId,
                'fecha' => now()->toDateString(),
            ]);

            Reserva::where(
                'reserva_id',
                $pago->reserva_id
            )->update([
                'estado' => 'pagada'
            ]);
        });

        return response()->json([
            'success' => true
        ]);
    }

    // RESERVAS - PRESENCIAL
    public function pagarPresencial($user, $reserva_id)
    {
        $reserva = Reserva::with('pago')
            ->findOrFail($reserva_id);

        if ((int) $reserva->cliente_id !== (int) $user->id) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        if (!$reserva->pago) {
            return response()->json([
                'message' => 'No existe pago'
            ], 400);
        }

        $reserva->pago->update([
            'metodo' => 'presencial'
        ]);

        return response()->json([
            'success' => true
        ]);
    }

    // PAQUETES - INICIAR PAYPAL
    public function iniciarPaquetePaypal(
        $user,
        $compra_paquete_id
    ) {
        $compra = CompraPaquete::with('paquete', 'pago')
            ->findOrFail($compra_paquete_id);

        if (
            (int) $compra->cliente_id !==
            (int) $user->id
        ) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        if ($compra->pago?->estado === 'aprobado') {
            return response()->json([
                'message' => 'El paquete ya fue pagado'
            ], 409);
        }

        $monto = number_format(
            $compra->paquete->precio_total,
            2,
            '.',
            ''
        );

        $paypal = $this->paypal();

        $order = $paypal->createOrder([
            'intent' => 'CAPTURE',

            'purchase_units' => [[
                'description' =>
                    'Paquete: ' .
                    $compra->paquete->nombre,

                'amount' => [
                    'currency_code' => 'USD',
                    'value' => $monto,
                ],
            ]],

            'application_context' => [
                'return_url' =>
                    config('app.url') .
                    '/api/pagos/paquete/capturar',

                'cancel_url' =>
                    config('app.url') .
                    '/api/pagos/cancelar',
            ],
        ]);

        if (isset($order['error'])) {
            return response()->json([
                'message' => 'Error PayPal'
            ], 500);
        }

        $pago = Pago::updateOrCreate(
            [
                'compra_paquete_id' =>
                    $compra->compra_paquete_id
            ],
            [
                'fecha' => now()->toDateString(),
                'monto' => $monto,
                'estado' => 'pendiente',
                'metodo' => 'paypal',
                'paypal_order_id' => $order['id'],
            ]
        );

        $approvalUrl = collect($order['links'])
            ->firstWhere('rel', 'approve')['href'];

        return response()->json([
            'approval_url' => $approvalUrl,
            'paypal_order_id' => $order['id']
        ]);
    }

    // PAQUETES - CAPTURAR PAYPAL
    public function capturarPaquetePaypal($orderId)
    {
        $pago = Pago::where(
            'paypal_order_id',
            $orderId
        )->firstOrFail();

        $paypal = $this->paypal();

        $result = $paypal->capturePaymentOrder($orderId);

        if (
            isset($result['error']) ||
            ($result['status'] ?? '') !== 'COMPLETED'
        ) {
            $pago->update([
                'estado' => 'fallido'
            ]);

            return response()->json([
                'success' => false
            ], 400);
        }

        $captureId =
            $result['purchase_units'][0]
            ['payments']['captures'][0]['id']
            ?? null;

        $pago->update([
            'estado' => 'aprobado',
            'paypal_capture_id' => $captureId,
            'fecha' => now()->toDateString(),
        ]);

        return response()->json([
            'success' => true
        ]);
    }

    // CANCELAR PAYPAL
    public function cancelarPaypal($orderId)
    {
        if ($orderId) {
            Pago::where(
                'paypal_order_id',
                $orderId
            )->update(['estado' => 'cancelado']);
        }

        return response()->json([
            'message' => 'Pago cancelado'
        ]);
    }

   public function confirmarPagoPresencial($user, $reserva_id)
    {
        $reserva = Reserva::with('pago')->findOrFail($reserva_id);

        if (!$reserva->pago) {
            return [
                'success' => false,
                'message' => 'No existe pago',
                'status' => 400
            ];
        }

        $reserva->pago()->update([
            'estado' => 'aprobado',
            'fecha' => now()->toDateString(),
        ]);

        return [
            'success' => true,
            'message' => 'Pago confirmado',
            'status' => 200
        ];
    }

    public function obtenerPagosProfesional(int $profesionalId): array
    {
        $pagos = Pago::with([
            'reserva.servicio',
            'reserva.cliente.user',
        ])
        ->whereHas('reserva.servicio', function ($q) use ($profesionalId) {
            $q->where('profesional_id', $profesionalId);
        })
        ->orderBy('fecha', 'desc')
        ->get();

        return $pagos->map(function ($pago) {

            return [
                'fecha' => $pago->fecha,
                'cliente' => $pago->reserva->cliente->user->name ?? '-',
                'servicio' => $pago->reserva->servicio->nombre ?? '-',
                'monto' => $pago->monto,
                'estado' => $pago->estado,
            ];
        })->toArray();
    }

    public function obtenerResumenPagosProfesional(int $profesionalId): array
    {
        $inicioMes = Carbon::now()->startOfMonth();
        $finMes = Carbon::now()->endOfMonth();

        $pagos = Pago::whereHas('reserva.servicio', function ($q) use ($profesionalId) {
            $q->where('profesional_id', $profesionalId);
        })
        ->whereBetween('fecha', [$inicioMes, $finMes])
        ->get();

        return [
            'total_mes' => (float) $pagos->sum('monto'),
            'pagado' => (float) $pagos->where('estado', 'aprobado')->sum('monto'),
            'pendiente' => (float) $pagos->where('estado', 'pendiente')->sum('monto'),
        ];
    }
}