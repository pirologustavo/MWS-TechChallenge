<?php

namespace App\Http\Controllers;

use App\Models\Funcionario;
use Illuminate\Http\Request;

class FuncionarioController extends Controller
{
    public function index()
    {
        $funcionarios = Funcionario::all(['funcid', 'nome', 'cargo']);

        return response()->json($funcionarios);
    }

    public function buscarMecanico(Request $request)
    {
        $busca = $request->query('q');

        $funcionarios = Funcionario::where('nome', 'like', "%{$busca}%")
            ->where('cargo', 'Mecânico')
            ->limit(10)
            ->get(['funcid', 'nome']);

        return response()->json($funcionarios);
    }
}
