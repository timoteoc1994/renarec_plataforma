<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AsociationController;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CiudadanoController;
use App\Http\Controllers\RecicladorController;
use App\Http\Controllers\AsociacionController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//recibir datos de la asociacion uso para el registro
Route::post('registro_asociacion', [AsociationController::class, 'store'])->name('registro_asociacion');
//enviar ciudades disponible para el registro
Route::get('ciudades_disponibles', [AsociationController::class, 'city'])->name('ciudades_disponibles');
//login asociaciones
Route::post('login', [AsociationController::class, 'login'])->name('login');


// Rutas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/ciudades_disponibles', [AuthController::class, 'getCities']);

// Rutas protegidas (requieren autenticación)
Route::middleware('auth:sanctum')->group(function () {
    // Rutas para todos los usuarios
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'getProfile']);

    // Rutas para ciudadanos
    Route::middleware(['role:ciudadano'])->prefix('ciudadano')->group(function () {
        Route::get('/solicitudes', [CiudadanoController::class, 'getSolicitudes']);
        Route::post('/solicitudes', [CiudadanoController::class, 'createSolicitud']);
        Route::get('/solicitudes/{id}', [CiudadanoController::class, 'getSolicitud']);
    });

    // Rutas para recicladores
    Route::middleware(['role:reciclador'])->prefix('reciclador')->group(function () {
        Route::get('/asignaciones', [RecicladorController::class, 'getAsignaciones']);
        Route::put('/asignaciones/{id}/actualizar', [RecicladorController::class, 'updateAsignacion']);
    });

    // Rutas para asociaciones
    Route::middleware(['role:asociacion'])->prefix('asociacion')->group(function () {
        Route::get('/recicladores', [AsociacionController::class, 'getRecicladores']);
        Route::post('/recicladores', [AuthController::class, 'registerRecycler']);
        Route::get('/solicitudes', [AsociacionController::class, 'getSolicitudes']);
        Route::post('/asignar', [AsociacionController::class, 'asignarSolicitud']);
    });
});
