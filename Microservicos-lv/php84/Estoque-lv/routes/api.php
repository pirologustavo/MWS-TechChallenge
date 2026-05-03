<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EstoqueController;

Route::post('/estoque/salvar', [EstoqueController::class, 'salvar']);
Route::get('/estoque/listar', [EstoqueController::class, 'listar']);
Route::get('/estoque/buscarPorId/{id}', [EstoqueController::class, 'buscarPorId']);
Route::put('/estoque/atualizarEstoque/{id}', [EstoqueController::class, 'atualizarEstoque']);
Route::get('/estoque/buscar', [EstoqueController::class, 'buscar']);
Route::post('/estoque/alterarEstoque', [EstoqueController::class, 'alterarEstoque']);
Route::post('/estoque/estornarItens', [EstoqueController::class, 'estornarItens']);
