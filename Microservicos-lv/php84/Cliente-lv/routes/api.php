<?php

use App\Http\Controllers\Api\ClienteController;
use Illuminate\Support\Facades\Route;

Route::post('/clientes', [ClienteController::class, 'store']);
Route::get('/clientes/buscar', [ClienteController::class, 'buscar']);
Route::get('/clientes', [ClienteController::class, 'index']);
Route::get('/clientes/buscarPorId/{id}', [ClienteController::class, 'buscarPorId']);
Route::put('/clientes/atualizarCliente/{id}', [ClienteController::class, 'atualizarCliente']);
Route::post('/clientes/deletarCliente/{id}', [ClienteController::class, 'deletarCliente']);
