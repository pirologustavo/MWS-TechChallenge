<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrdemServicoController;

Route::post('/os/salvar', [OrdemServicoController::class, 'store']);
