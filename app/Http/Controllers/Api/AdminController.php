<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AdminService;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct(
        private AdminService $service
    ) {}

    public function dashboard(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        if ($user->role !== 'admin') {
            return response()->json([
                'message' => 'Forbidden'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $this->service->getDashboard()
        ]);
    }

    public function clients(Request $request)
    {
        $user = $request->user();

        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $this->service->getClients()
        ]);
    }

    public function professionals(Request $request)
    {
        $user = $request->user();

        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $this->service->getProfessionals()
        ]);
    }

    public function getPagos(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Forbidden'
            ], 403);
        }

        $pagos = $this->service->getAllPayments();

        return response()->json([
            'success' => true,
            'data' => $pagos
        ]);
    }

    public function pagosTotales(Request $request)
    {
        try {
            $data = $this->service->getPaymentsSummary();

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo resumen de pagos',
            ], 500);
        }
    }
}