<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reserva;
use App\Services\VideoCallService;

class VideoCallController extends Controller
{
    protected $videoCallService;

    public function __construct(VideoCallService $videoCallService)
    {
        $this->videoCallService = $videoCallService;
    }

    public function token($reserva_id, Request $request)
    {
        $reserva = Reserva::findOrFail($reserva_id);

        // 🧪 MODO DEMO: si no hay usuario autenticado, usamos uno fake
        $user = $request->user() ?? (object)[
            "id" => "demo-user"
        ];

        $data = $this->videoCallService->generarToken($reserva, $user);

        return response()->json([
            "success" => true,
            "data" => $data
        ]);
    }
}