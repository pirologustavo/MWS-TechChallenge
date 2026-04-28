<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FuncionarioController;

Route::get('/funcionario/buscarMecanico', [FuncionarioController::class, 'buscarMecanico']);
