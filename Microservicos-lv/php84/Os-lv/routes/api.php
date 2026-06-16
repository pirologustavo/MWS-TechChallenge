<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrdemServicoController;

Route::get('/ping', function () {
    return response()->json(['ping' => true]);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/os/salvar', [OrdemServicoController::class, 'store']);
    Route::get('/os/listar', [OrdemServicoController::class, 'listar']);
    Route::get('/os/buscarPorId/{id}', [OrdemServicoController::class, 'buscarPorId']);
    Route::match(['PUT', 'POST'], '/os/atualizarOs/{id}', [OrdemServicoController::class, 'atualizarOs']);
    Route::get('/os/listarRecebidos', [OrdemServicoController::class, 'listarRecebidos']);
    Route::post("/os/emDiagnostico/{id}", [OrdemServicoController::class, 'emDiagnostico']);
    Route::post("/os/analiseOs/{id}", [OrdemServicoController::class, 'analiseOs']);
    Route::get('/os/listarAguardandoAprovacao', [OrdemServicoController::class, 'listarAguardandoAprovacao']);
    Route::post("/os/aprovarOs/{id}", [OrdemServicoController::class, 'aprovarOs']);
    Route::post("/os/listarExecucao", [OrdemServicoController::class, 'listarExecucao']);
    Route::post("/os/finalizarOs/{id}", [OrdemServicoController::class, 'finalizarOs']);
    Route::post("/os/entregar/{id}", [OrdemServicoController::class, 'entregarOs']);
    Route::post("/os/deletarOs/{id}", [OrdemServicoController::class, 'deletarOs']);
});
