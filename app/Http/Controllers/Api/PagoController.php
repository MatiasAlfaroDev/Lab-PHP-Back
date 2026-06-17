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

    public function confirmarPresencial(Request $request, $reserva_id)
    {
        $result = $this->pagoService->confirmarPagoPresencial(
            $request->user(),
            $reserva_id
        );

        return response()->json($result, $result['status'] ?? 200);
    }

    public function resumenProfesional(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'professional') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $data = $this->pagoService->obtenerResumenPagosProfesional($user->id);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function pagosProfesional(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'professional') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $data = $this->pagoService->obtenerPagosProfesional($user->id);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}