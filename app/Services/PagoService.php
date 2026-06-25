<?php

namespace App\Services;

use App\Models\Pago;
use App\Models\Reserva;
use App\Models\CompraPaquete;
use App\Models\User;
use App\Notifications\ReservaNotification;
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
        $frontendUrl = config('app.frontend_url');

        try {
            $pago = Pago::where(
                'paypal_order_id',
                $orderId
            )->firstOrFail();
        } catch (\Throwable $e) {
            return redirect($frontendUrl . '/client/reservas?pago=error&message=' . urlencode('Orden de pago no encontrada'));
        }

        $paypal = $this->paypal();

        $result = $paypal->capturePaymentOrder($orderId);

        if (
            isset($result['error']) ||
            ($result['status'] ?? '') !== 'COMPLETED'
        ) {
            $pago->update([
                'estado' => 'fallido'
            ]);

            return redirect($frontendUrl . '/client/reservas?pago=error&message=' . urlencode('Pago fallido'));
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

        $reservaActualizada = Reserva::with('servicio')->find($pago->reserva_id);
        if ($reservaActualizada) {
            $this->notificarPago(
                $reservaActualizada,
                'Pago Aprobado',
                "Tu pago para el servicio: {$reservaActualizada->servicio->nombre} fue aprobado",
                "Se aprobó el pago de la reserva para el servicio: {$reservaActualizada->servicio->nombre}"
            );
        }

        return redirect($frontendUrl . '/client/reservas?pago=exito');
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

        $reservaActualizada = Reserva::with('servicio')->find($pago->reserva_id);
        if ($reservaActualizada) {
            $this->notificarPago(
                $reservaActualizada,
                'Pago Aprobado',
                "Tu pago para el servicio: {$reservaActualizada->servicio->nombre} fue aprobado",
                "Se aprobó el pago de la reserva para el servicio: {$reservaActualizada->servicio->nombre}"
            );
        }

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
        $frontendUrl = config('app.frontend_url');

        try {
            $pago = Pago::where(
                'paypal_order_id',
                $orderId
            )->firstOrFail();
        } catch (\Throwable $e) {
            return redirect($frontendUrl . '/client/packages?pago=error&message=' . urlencode('Orden de pago no encontrada'));
        }

        $paypal = $this->paypal();

        $result = $paypal->capturePaymentOrder($orderId);

        if (
            isset($result['error']) ||
            ($result['status'] ?? '') !== 'COMPLETED'
        ) {
            $pago->update([
                'estado' => 'fallido'
            ]);

            return redirect($frontendUrl . '/client/packages?pago=error&message=' . urlencode('Pago fallido'));
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

        $compraActualizada = CompraPaquete::with('paquete')->find($pago->compra_paquete_id);
        if ($compraActualizada) {
            $this->notificarPagoPaquete(
                $compraActualizada,
                'Pago de Paquete Aprobado',
                "Tu pago para el paquete: {$compraActualizada->paquete->nombre} fue aprobado",
                "Se vendió el paquete: {$compraActualizada->paquete->nombre}"
            );
        }

        return redirect($frontendUrl . '/client/packages?pago=exito');
    }

    // CANCELAR PAYPAL
    public function cancelarPaypal($orderId)
    {
        $frontendUrl = config('app.frontend_url');
        $redirectPath = '/client/reservas';

        if ($orderId) {
            $pago = Pago::where(
                'paypal_order_id',
                $orderId
            )->first();

            if ($pago) {
                $pago->update(['estado' => 'cancelado']);

                if ($pago->reserva_id) {
                    $reservaCancelada = Reserva::with('servicio')->find($pago->reserva_id);
                    if ($reservaCancelada) {
                        $this->notificarPago(
                            $reservaCancelada,
                            'Pago Cancelado',
                            "Tu pago para el servicio: {$reservaCancelada->servicio->nombre} fue cancelado",
                            "El pago para el servicio: {$reservaCancelada->servicio->nombre} fue cancelado"
                        );
                    }
                } elseif ($pago->compra_paquete_id) {
                    $compraCancelada = CompraPaquete::with('paquete')->find($pago->compra_paquete_id);
                    if ($compraCancelada) {
                        $this->notificarPagoPaquete(
                            $compraCancelada,
                            'Pago Cancelado',
                            "Tu pago para el paquete: {$compraCancelada->paquete->nombre} fue cancelado"
                        );
                    }
                }

                $redirectPath = $pago->compra_paquete_id ? '/client/packages' : '/client/reservas';
            }
        }

        return redirect($frontendUrl . $redirectPath . '?pago=cancelado');
    }

   public function confirmarPagoPresencial($user, $reserva_id)
    {
        $reserva = Reserva::with('pago', 'servicio')->findOrFail($reserva_id);

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

        $this->notificarPago(
            $reserva,
            'Pago Registrado',
            "Tu pago para el servicio: {$reserva->servicio->nombre} fue registrado por el profesional",
            "Registraste el pago presencial para el servicio: {$reserva->servicio->nombre}"
        );

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

    private function notificarPago(Reserva $reserva, string $tipo, string $mensajeCliente, string $mensajeProfesional): void
    {
        if (!$reserva->relationLoaded('servicio')) {
            $reserva->load('servicio');
        }

        if (!$reserva->servicio) {
            return;
        }

        $cliente = User::find($reserva->cliente_id);
        $profesional = User::find($reserva->servicio->profesional_id);

        $cliente?->notify(new ReservaNotification($tipo, $mensajeCliente, $reserva->fecha, $reserva->hora));
        $profesional?->notify(new ReservaNotification($tipo, $mensajeProfesional, $reserva->fecha, $reserva->hora));
    }

    private function notificarPagoPaquete(CompraPaquete $compra, string $tipo, string $mensajeCliente, ?string $mensajeProfesional = null): void
    {
        if (!$compra->relationLoaded('paquete')) {
            $compra->load('paquete');
        }

        $cliente = User::find($compra->cliente_id);
        $fecha = now()->toDateString();
        $hora = now()->format('H:i:s');

        $cliente?->notify(new ReservaNotification($tipo, $mensajeCliente, $fecha, $hora));

        if ($mensajeProfesional !== null) {
            $profesional = $compra->paquete?->profesional?->user;
            $profesional?->notify(new ReservaNotification($tipo, $mensajeProfesional, $fecha, $hora));
        }
    }
}