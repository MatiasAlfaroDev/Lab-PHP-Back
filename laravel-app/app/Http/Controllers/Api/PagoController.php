<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PagoService;
use Illuminate\Http\Request;

class PagoController extends Controller
{
    protected $pagoService;

    public function __construct(PagoService $pagoService)
    {
        $this->pagoService = $pagoService;
    }

    // RESERVAS

    public function iniciarReserva(Request $request, $reserva_id)
    {
        return $this->pagoService->iniciarReservaPaypal(
            $request->user(),
            $reserva_id
        );
    }

    public function capturarReserva(Request $request)
    {
        return $this->pagoService->capturarReservaPaypal(
            $request->query('token')
        );
    }

    public function capturarReservaSDK(Request $request, $reserva_id)
    {
        return $this->pagoService->capturarReservaSDK(
            $reserva_id,
            $request->paypal_order_id
        );
    }

    public function pagarPresencial(Request $request, $reserva_id)
    {
        return $this->pagoService->pagarPresencial(
            $request->user(),
            $reserva_id
        );
    }

    // PAQUETES

    public function iniciarPaquete(Request $request, $compra_paquete_id)
    {
        return $this->pagoService->iniciarPaquetePaypal(
            $request->user(),
            $compra_paquete_id
        );
    }

    public function capturarPaquete(Request $request)
    {
        return $this->pagoService->capturarPaquetePaypal(
            $request->query('token')
        );
    }

    // CANCELAR PAYPAL

    public function cancelar(Request $request)
    {
        return $this->pagoService->cancelarPaypal(
            $request->query('token')
        );
    }
}