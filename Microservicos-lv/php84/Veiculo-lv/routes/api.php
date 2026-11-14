<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\VeiculoController;
use App\Http\Middleware\InternalServiceAuth;
use App\Http\Middleware\SharedServiceTokenAuth;
use App\Models\Veiculo;

Route::get('/ping', function () {
    return response()->json(['ping' => true]);
});

Route::middleware(InternalServiceAuth::class)->prefix('internal/v1')->group(function () {
    Route::get('/veiculos', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate(['ids' => 'required|array|min:1|max:200', 'ids.*' => 'integer|min:1']);
        return response()->json(Veiculo::whereIn('carid', $validated['ids'])
            ->get(['carid', 'clientid', 'modelo', 'placa']));
    });
});

Route::middleware(SharedServiceTokenAuth::class)->group(function () {
    Route::post('/veiculos/salvar', [VeiculoController::class, 'salvar']);
    Route::get('/veiculos/listar', [VeiculoController::class, 'listar']);
    Route::get('/veiculos/buscarPorId/{id}', [VeiculoController::class, 'buscarPorId']);
    Route::put('/veiculos/atualizarVeiculo/{id}', [VeiculoController::class, 'atualizarVeiculo']);
    Route::get('veiculos/buscarVeiculoCliente/{id}', [VeiculoController::class, 'buscarVeiculoCliente']);
    Route::post('/veiculos/deletarVeiculo/{id}', [VeiculoController::class, 'deletarVeiculo']);
});


