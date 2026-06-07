<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ServicioController;
use App\Http\Controllers\Api\PagoController;
use App\Http\Controllers\Api\PaqueteController;
use App\Http\Controllers\Api\ProfesionalController;
use App\Http\Controllers\Api\DisponibilidadController;
use App\Http\Controllers\Api\ReservaController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\CompraPaqueteController;
use App\Http\Controllers\Api\GeocodingController;
use App\Http\Controllers\Api\VideoCallController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ExcepcionController;

/*
|--------------------------------------------------------------------------
| AUTH (público)
|--------------------------------------------------------------------------
*/

Route::post('/login', [UserController::class, 'login']);
Route::post('/auth/login', [UserController::class, 'login']);

Route::post('/register', [UserController::class, 'register']);
Route::post('/auth/register', [UserController::class, 'register']);

// Servicios (público para el front)
Route::get('/servicios', [ServicioController::class, 'index']);

// Geocoding (público) — convierte dirección en coordenadas
Route::get('/geocoding', [GeocodingController::class, 'geocodificar']);

// Perfil público de profesional
Route::get('/profesionales/{id}', [ProfesionalController::class, 'show']);

// Disponibilidad (público)
Route::get('/servicios/{id}/dias-disponibles', [DisponibilidadController::class, 'diasDisponibles']);
Route::get('/servicios/{id}/disponibilidad',   [DisponibilidadController::class, 'byServicio']);
Route::get('/servicios/{id}/slots',            [DisponibilidadController::class, 'slots']);

// Callbacks de PayPal (llamados directamente por PayPal, sin token)
Route::get('/pagos/reserva/capturar', [PagoController::class, 'capturarReserva']);
Route::get('/pagos/paquete/capturar', [PagoController::class, 'capturarPaquete']);
Route::get('/pagos/cancelar', [PagoController::class, 'cancelar']);

/*
|--------------------------------------------------------------------------
| RUTAS PROTEGIDAS (Sanctum)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/videollamada/token/{reserva}', [VideoCallController::class, 'token']);
    Route::post('/videollamada/{id}/estado', [ReservaController::class, 'actualizarEstadoVideollamada']);
    /*
    |------------------------------------------
    | Usuario logueado
    |------------------------------------------
    */
    Route::get('/me', [UserController::class, 'me']);
    Route::post('/logout', [UserController::class, 'logout']);

    /*
    |------------------------------------------
    | Servicios (Profesionales)
    |------------------------------------------
    */
    Route::apiResource('servicios', ServicioController::class)->except(['index']);
     Route::post('/servicios', [ServicioController::class, 'store']);

    Route::get('/mis-servicios', [ServicioController::class, 'misServicios']);

    Route::middleware('auth:sanctum')->group(function () {
    Route::post('/reservas', [ReservaController::class, 'store']);
    });

    // Reservas
    Route::post('/reservas',              [ReservaController::class, 'store']);
    Route::get('/mis-reservas',           [ReservaController::class, 'misReservas']);
    Route::get('/mi-agenda',              [ReservaController::class, 'agendaProfesional']);
    Route::get('/reservas/pendientes', [ReservaController::class, 'pendientesProfesional']);
    Route::put('/reservas/{id}/cancelar', [ReservaController::class, 'cancel']);
    Route::put('/reservas/{id}/estado', [ReservaController::class, 'cambiarEstado']);
    Route::put('/reservas/{id}/no-asistida', [ReservaController::class, 'noAsistida']);
    Route::post('/reservas/{id}/pago-presencial', [PagoController::class, 'confirmarPresencial']);

    // Disponibilidad (protegido)
    Route::put('/servicios/{id}/disponibilidad', [DisponibilidadController::class, 'bulkUpdate']);
    Route::get('/excepciones', [ExcepcionController::class, 'index']);
    Route::post('/excepciones', [ExcepcionController::class, 'store']);
    Route::delete('/excepciones/{id}', [ExcepcionController::class, 'destroy']);

    /*
    |------------------------------------------
    | Reservas (Clientes)
    |------------------------------------------
    */
    //Route::apiResource('reservas', ReservaController::class);

    Route::middleware('auth:sanctum')->get(
    '/clientes',
    [ClienteController::class, 'index']
    );

    /*
    |------------------------------------------
    | Pagos (PayPal)
    |------------------------------------------
    */
    Route::post('/pagos/reserva/{reserva_id}/paypal', [PagoController::class, 'iniciarReserva']);
    Route::post('/pagos/reserva/{reserva_id}/presencial', [PagoController::class, 'pagarPresencial']);
    Route::post('/pagos/reserva/{reserva_id}/capturar-sdk', [PagoController::class, 'capturarReservaSDK']);
    Route::post('/pagos/paquete/{compra_paquete_id}/paypal', [PagoController::class, 'iniciarPaquete']);
    /*
    |------------------------------------------
    | Paquetes de sesiones
    |------------------------------------------
    */
    Route::apiResource('paquetes', PaqueteController::class);
    Route::post('/compra-paquetes', [CompraPaqueteController::class, 'store']);
    Route::get('/mis-compras-paquetes', [CompraPaqueteController::class, 'misPaquetes']);
    Route::get('/mis-paquetes', [PaqueteController::class, 'misPaquetes']);
    Route::get('/compra-paquetes/{id}', [CompraPaqueteController::class, 'show']);
    Route::delete('/compra-paquetes/{id}', [CompraPaqueteController::class, 'destroy']);


    Route::middleware('auth:sanctum')->group(function () {

    Route::get('/notificaciones', [NotificationController::class, 'index']);
    Route::get('/notificaciones/no-leidas', [NotificationController::class, 'noLeidas']);
    Route::post('/notificaciones/{id}/leer', [NotificationController::class, 'leer']);
    Route::post('/notificaciones/leer-todas', [NotificationController::class, 'leerTodas']);
});
});