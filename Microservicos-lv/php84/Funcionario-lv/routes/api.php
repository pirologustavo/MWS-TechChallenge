<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FuncionarioController;

Route::get('/ping', function () {
    return response()->json(['ping' => true]);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/funcionario', [FuncionarioController::class, 'index']);
    Route::get('/funcionario/buscarMecanico', [FuncionarioController::class, 'buscarMecanico']);
});
