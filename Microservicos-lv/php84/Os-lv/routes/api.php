<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrdemServicoController;

Route::post('/os/salvar', [OrdemServicoController::class, 'store']);
Route::get('/os/listar', [OrdemServicoController::class, 'listar']);
Route::get('/os/buscarPorId/{id}', [OrdemServicoController::class, 'buscarPorId']);
