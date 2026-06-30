<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FuncionarioController;
use App\Http\Controllers\AuthController;

Route::get('/ping', function () {
    return response()->json(['ping' => true]);
});

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/funcionario', [FuncionarioController::class, 'index']);
    Route::get('/funcionario/buscarMecanico', [FuncionarioController::class, 'buscarMecanico']);
});
