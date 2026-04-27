<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EstoqueController;

Route::get('/estoque/listar', [EstoqueController::class, 'listar']);
Route::get('/estoque/bucarPorId/{id}', [EstoqueController::class, 'buscarPorId']);
Route::put('/estoque/atualizarEstoque/{id}', [EstoqueController::class, 'atualizarEstoque']);
