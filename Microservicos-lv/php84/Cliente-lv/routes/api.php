<?php

use App\Http\Controllers\Api\ClienteController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', function () {
    return response()->json(['ping' => true]);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/clientes', [ClienteController::class, 'store']);
    Route::get('/clientes/buscar', [ClienteController::class, 'buscar']);
    Route::get('/clientes', [ClienteController::class, 'index']);
    Route::get('/clientes/buscarPorId/{id}', [ClienteController::class, 'buscarPorId']);
    Route::put('/clientes/atualizarCliente/{id}', [ClienteController::class, 'atualizarCliente']);
    Route::post('/clientes/deletarCliente/{id}', [ClienteController::class, 'deletarCliente']);
});
