<?php

use App\Http\Controllers\Api\ClienteController;
use App\Http\Middleware\InternalServiceAuth;
use App\Http\Middleware\SharedServiceTokenAuth;
use App\Models\Cliente;
use Illuminate\Support\Facades\Route;

Route::get('/ping', function () {
    return response()->json(['ping' => true]);
});

Route::middleware(SharedServiceTokenAuth::class)->group(function () {
    Route::post('/clientes', [ClienteController::class, 'store']);
    Route::get('/clientes/buscar', [ClienteController::class, 'buscar']);
    Route::get('/clientes', [ClienteController::class, 'index']);
    Route::get('/clientes/buscarPorId/{id}', [ClienteController::class, 'buscarPorId']);
    Route::put('/clientes/atualizarCliente/{id}', [ClienteController::class, 'atualizarCliente']);
    Route::post('/clientes/deletarCliente/{id}', [ClienteController::class, 'deletarCliente']);
    Route::get('/clientes/{id}/auditoria', [ClienteController::class, 'auditoria']);
});

Route::middleware(InternalServiceAuth::class)->prefix('internal/v1')->group(function () {
    Route::get('/clientes', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'ids' => 'sometimes|array|max:200',
            'ids.*' => 'integer|min:1',
            'cpf' => 'sometimes|string|max:20',
        ]);
        abort_unless(isset($validated['ids']) || isset($validated['cpf']), 422, 'Informe ids ou cpf.');
        $query = Cliente::query()->select(['clientid', 'nome', 'email', 'telefone']);
        if (isset($validated['ids'])) $query->whereIn('clientid', $validated['ids']);
        if (isset($validated['cpf'])) $query->where('cpf', $validated['cpf']);
        return response()->json($query->get());
    });
});
