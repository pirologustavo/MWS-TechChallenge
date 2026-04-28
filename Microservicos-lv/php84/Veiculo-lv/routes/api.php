<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\VeiculoController;

Route::post('/veiculos/salvar', [VeiculoController::class, 'salvar']);
Route::get('/veiculos/listar', [VeiculoController::class, 'listar']);
Route::get('/veiculos/bucarPorId/{id}', [VeiculoController::class, 'buscarPorId']);
Route::put('/veiculos/atualizarVeiculo/{id}', [VeiculoController::class, 'atualizarVeiculo']);
Route::get('veiculos/buscarVeiculoCliente/{id}', [VeiculoController::class, 'buscarVeiculoCliente']);
