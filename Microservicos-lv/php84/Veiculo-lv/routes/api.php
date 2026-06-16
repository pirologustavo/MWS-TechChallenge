<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\VeiculoController;

Route::get('/ping', function () {
    return response()->json(['ping' => true]);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/veiculos/salvar', [VeiculoController::class, 'salvar']);
    Route::get('/veiculos/listar', [VeiculoController::class, 'listar']);
    Route::get('/veiculos/buscarPorId/{id}', [VeiculoController::class, 'buscarPorId']);
    Route::put('/veiculos/atualizarVeiculo/{id}', [VeiculoController::class, 'atualizarVeiculo']);
    Route::get('veiculos/buscarVeiculoCliente/{id}', [VeiculoController::class, 'buscarVeiculoCliente']);
    Route::post('/veiculos/deletarVeiculo/{id}', [VeiculoController::class, 'deletarVeiculo']);
});


