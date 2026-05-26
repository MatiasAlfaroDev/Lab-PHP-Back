<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pago;
use App\Models\Reserva;
use App\Models\CompraPaquete;
use Illuminate\Http\Request;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class PagoController extends Controller
{
    private function paypal(): PayPalClient
    {
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $provider->getAccessToken();
        return $provider;
    }

    // POST /pagos/reserva/{reserva_id}/iniciar
    public function iniciarReserva(Request $request, $reserva_id)
    {
        $reserva = Reserva::with('servicio')->findOrFail($reserva_id);

        if ((int) $reserva->cliente_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        if ($reserva->pago && $reserva->pago->estado === 'aprobado') {
            return response()->json(['message' => 'Esta reserva ya fue pagada'], 409);
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
            return response()->json(['message' => 'Error al crear orden PayPal', 'detail' => $order['error']], 500);
        }

        Pago::updateOrCreate(
            ['reserva_id' => $reserva->reserva_id],
            [
                'compra_paquete_id' => null,
                'fecha' => now()->toDateString(),
                'monto' => $monto,
                'estado' => 'pendiente',
                'paypal_order_id' => $order['id'],
            ]
        );

        $approvalUrl = collect($order['links'])->firstWhere('rel', 'approve')['href'];

        return response()->json(['approval_url' => $approvalUrl, 'paypal_order_id' => $order['id']]);
    }

    // POST /pagos/reserva/{reserva_id}/capturar  (llamado desde el SDK de PayPal)
    public function capturarReservaSDK(Request $request, $reserva_id)
    {
        $orderId = $request->input('paypal_order_id');
        $pago    = Pago::where('reserva_id', $reserva_id)
                       ->where('paypal_order_id', $orderId)
                       ->firstOrFail();

        $paypal = $this->paypal();
        $result = $paypal->capturePaymentOrder($orderId);

        if (isset($result['error']) || ($result['status'] ?? '') !== 'COMPLETED') {
            $pago->update(['estado' => 'fallido']);
            return response()->json(['success' => false, 'message' => 'Pago fallido'], 400);
        }

        $captureId = $result['purchase_units'][0]['payments']['captures'][0]['id'] ?? null;
        $pago->update(['estado' => 'aprobado', 'paypal_capture_id' => $captureId]);
        Reserva::where('reserva_id', $reserva_id)->update(['estado' => 'pagada']);

        return response()->json(['success' => true]);
    }

    // GET /pagos/reserva/capturar?token=ORDER_ID
    public function capturarReserva(Request $request)
    {
        $orderId = $request->query('token');

        $pago = Pago::where('paypal_order_id', $orderId)->firstOrFail();

        $paypal = $this->paypal();
        $result = $paypal->capturePaymentOrder($orderId);

        if (isset($result['error']) || ($result['status'] ?? '') !== 'COMPLETED') {
            $pago->update(['estado' => 'fallido']);
            return response()->json(['message' => 'Pago fallido', 'detail' => $result], 400);
        }

        $captureId = $result['purchase_units'][0]['payments']['captures'][0]['id'] ?? null;

        $pago->update([
            'estado' => 'aprobado',
            'paypal_capture_id' => $captureId,
        ]);

        if ($pago->reserva_id) {
            Reserva::where('reserva_id', $pago->reserva_id)->update(['estado' => 'pagada']);
        }

        return response()->json(['message' => 'Pago aprobado', 'pago_id' => $pago->pago_id]);
    }

    // POST /pagos/paquete/{compra_paquete_id}/iniciar
    public function iniciarPaquete(Request $request, $compra_paquete_id)
    {
        $compra = CompraPaquete::with('paquete')->findOrFail($compra_paquete_id);

        if ($compra->cliente_id !== $request->user()->cliente?->cliente_id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        if ($compra->pago && $compra->pago->estado === 'aprobado') {
            return response()->json(['message' => 'Este paquete ya fue pagado'], 409);
        }

        $monto = number_format($compra->paquete->precio_total, 2, '.', '');

        $paypal = $this->paypal();
        $order = $paypal->createOrder([
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'description' => 'Paquete: ' . $compra->paquete->nombre,
                'amount' => [
                    'currency_code' => 'USD',
                    'value' => $monto,
                ],
            ]],
            'application_context' => [
                'return_url' => config('app.url') . '/api/pagos/paquete/capturar',
                'cancel_url' => config('app.url') . '/api/pagos/cancelar',
            ],
        ]);

        if (isset($order['error'])) {
            return response()->json(['message' => 'Error al crear orden PayPal', 'detail' => $order['error']], 500);
        }

        Pago::updateOrCreate(
            ['compra_paquete_id' => $compra->compra_paquete_id],
            [
                'reserva_id' => null,
                'fecha' => now()->toDateString(),
                'monto' => $monto,
                'estado' => 'pendiente',
                'paypal_order_id' => $order['id'],
            ]
        );

        $approvalUrl = collect($order['links'])->firstWhere('rel', 'approve')['href'];

        return response()->json(['approval_url' => $approvalUrl, 'paypal_order_id' => $order['id']]);
    }

    // GET /pagos/paquete/capturar?token=ORDER_ID
    public function capturarPaquete(Request $request)
    {
        $orderId = $request->query('token');

        $pago = Pago::where('paypal_order_id', $orderId)->firstOrFail();

        $paypal = $this->paypal();
        $result = $paypal->capturePaymentOrder($orderId);

        if (isset($result['error']) || ($result['status'] ?? '') !== 'COMPLETED') {
            $pago->update(['estado' => 'fallido']);
            return response()->json(['message' => 'Pago fallido', 'detail' => $result], 400);
        }

        $captureId = $result['purchase_units'][0]['payments']['captures'][0]['id'] ?? null;

        $pago->update([
            'estado' => 'aprobado',
            'paypal_capture_id' => $captureId,
        ]);

        return response()->json(['message' => 'Pago aprobado', 'pago_id' => $pago->pago_id]);
    }

    // GET /pagos/cancelar
    public function cancelar(Request $request)
    {
        $orderId = $request->query('token');
        if ($orderId) {
            Pago::where('paypal_order_id', $orderId)->update(['estado' => 'cancelado']);
        }
        return response()->json(['message' => 'Pago cancelado']);
    }
}
