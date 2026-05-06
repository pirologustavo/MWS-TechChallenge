<?php

use App\Http\Controllers\Api\ClienteController;
use Illuminate\Support\Facades\Route;

// A URL será: http://localhost:8001/api/clientes
Route::post('/clientes', [ClienteController::class, 'store']);
Route::get('/clientes/buscar', [ClienteController::class, 'buscar']);
Route::get('/clientes', [ClienteController::class, 'index']);
Route::get('/clientes/bucarPorId/{id}', [ClienteController::class, 'buscarPorId']);
Route::put('/clientes/atualizarCliente/{id}', [ClienteController::class, 'atualizarCliente']);
Route::post('/clientes/deletarCliente/{id}', [ClienteController::class, 'deletarCliente']);
