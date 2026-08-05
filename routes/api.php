<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiCensoController;

//  RUTA PÚBLICA (Para que la app móvil inicie sesión con Username y PIN)
Route::post('/login', [ApiCensoController::class, 'login']);

//  RUTAS PROTEGIDAS (Requieren Token de Sanctum en la App)
Route::middleware('auth:sanctum')->group(function () {
    
    // Datos del usuario logueado
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Operaciones principales del escáner
    Route::get('/buscar', [ApiCensoController::class, 'buscar']);
    Route::post('/guardar-hallazgo', [ApiCensoController::class, 'guardar']);
    
    // Historiales de auditoría
    Route::get('/historial-producto/{id}', [ApiCensoController::class, 'historialProducto']);
    Route::get('/mi-historial', [ApiCensoController::class, 'miHistorial']);
    
    //  NUEVO: Impresión de etiquetas Zebra
    Route::post('/imprimir-etiqueta', [ApiCensoController::class, 'imprimirEtiqueta']);
});