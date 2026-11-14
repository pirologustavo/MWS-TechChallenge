<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FuncionarioController;
use App\Http\Middleware\InternalServiceAuth;
use App\Models\Funcionario;
use Laravel\Sanctum\PersonalAccessToken;
use App\Http\Controllers\AuthController;

Route::get('/ping', function () {
    return response()->json(['ping' => true]);
});

Route::middleware(InternalServiceAuth::class)->prefix('internal/v1')->group(function () {
    Route::get('/token', function (\Illuminate\Http\Request $request) {
        $plainTextToken = $request->bearerToken();
        $token = $plainTextToken ? PersonalAccessToken::findToken($plainTextToken) : null;
        $expiresAt = $token?->expires_at;
        if ($token && !$expiresAt && config('sanctum.expiration')) {
            $expiresAt = $token->created_at->addMinutes((int) config('sanctum.expiration'));
        }
        $valid = $token
            && $token->tokenable instanceof Funcionario
            && (!$expiresAt || $expiresAt->isFuture());
        return response()->json(['valid' => (bool) $valid]);
    });
    Route::get('/funcionarios', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate(['ids' => 'required|array|min:1|max:200', 'ids.*' => 'integer|min:1']);
        return response()->json(Funcionario::whereIn('funcid', $validated['ids'])
            ->where('cargo', 'Mecânico')->get(['funcid', 'nome', 'cargo']));
    });
});

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/funcionario', [FuncionarioController::class, 'index']);
    Route::get('/funcionario/buscarMecanico', [FuncionarioController::class, 'buscarMecanico']);
});
