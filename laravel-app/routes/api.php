<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ServicioController;
use App\Http\Controllers\Api\PagoController;
use App\Http\Controllers\Api\PaqueteController;
use App\Http\Controllers\Api\ProfesionalController;
use App\Http\Controllers\Api\DisponibilidadController;
use App\Http\Controllers\Api\ReservaController;

/*
|--------------------------------------------------------------------------
| AUTH (público)
|--------------------------------------------------------------------------
*/

Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);

// Servicios (público para el front)
Route::get('/servicios', [ServicioController::class, 'index']);

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

    // Reservas
    Route::post('/reservas',              [ReservaController::class, 'store']);
    Route::get('/mis-reservas',           [ReservaController::class, 'misReservas']);
    Route::get('/mi-agenda',              [ReservaController::class, 'agendaProfesional']);
    Route::put('/reservas/{id}/cancelar', [ReservaController::class, 'cancel']);

    // Disponibilidad (protegido)
    Route::put('/servicios/{id}/disponibilidad', [DisponibilidadController::class, 'bulkUpdate']);

    /*
    |------------------------------------------
    | Reservas (Clientes)
    |------------------------------------------
    */
    //Route::apiResource('reservas', ReservaController::class);

    /*
    |------------------------------------------
    | Pagos (PayPal)
    |------------------------------------------
    */
    Route::post('/pagos/reserva/{reserva_id}/iniciar',   [PagoController::class, 'iniciarReserva']);
    Route::post('/pagos/reserva/{reserva_id}/capturar', [PagoController::class, 'capturarReservaSDK']);
    Route::post('/pagos/paquete/{compra_paquete_id}/iniciar', [PagoController::class, 'iniciarPaquete']);

    /*
    |------------------------------------------
    | Paquetes de sesiones
    |------------------------------------------
    */
    Route::apiResource('paquetes', PaqueteController::class);

});