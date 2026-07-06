<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Funcionario;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'usr' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = Funcionario::where('usr', $request->usr)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Credenciais inválidas.'], 411);
        }

        // Como User usa HasApiTokens e Authenticatable, isso aqui roda com perfeição:
        $token = $user->createToken('mws-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'nome'  => $user->nome, // Se na tabela for 'nome', certifique-se de adicionar no #Fillable do User.php se necessário
                'cargo' => $user->cargo
            ]
        ]);
    }
}
