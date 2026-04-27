<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClienteController extends Controller
{
    public function store(Request $request)
    {
        // Validação
        $validator = Validator::make($request->all(), [
            'nome'     => 'required|string|min:3',
            'email'    => 'required|email|unique:clientes',
            'cpf'      => 'required|string|size:11|unique:clientes',
            'cep'      => 'required|string',
            'endereco' => 'required|string',
            'estado'   => 'required|string|size:2',
            'cidade'   => 'required|string',
            'telefone' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $cliente = Cliente::create($request->all());

        return response()->json([
            'message' => 'Cliente cadastrado com sucesso no Microserviço!',
            'data'    => $cliente
        ], 201);
    }

    public function buscar(Request $request)
    {
        $busca = $request->query('q');

        $clientes = Cliente::where('nome', 'like', "%{$busca}%")
        ->limit(10)
        ->get(['clientid', 'nome']);

        return response()->json($clientes);
    }

    public function index()
    {
        return response()->json(Cliente::all());
    }

    public function buscarPorId($id)
    {
        $cliente = Cliente::where('clientid', $id)->first();

        if (!$cliente) {
            return response()->json(['message' => 'Cliente não encontrado!'], 404);
        }

        return response()->json($cliente);
    }

    public function atualizarCliente(Request $request, string $id)
    {
        $cliente = Cliente::where('clientid', $id)->first();

        if (!$cliente) {
            return response()->json(['message' => 'Cliente não encontrado!'], 404);
        }

        $dados = $request->all();

        if (isset($dados['cpf'])) {
            $dados['cpf'] = preg_replace("/\D/", "", $dados['cpf']);
        }

        $cliente->update($dados);

        return response()->json([
            'message' => 'Cliente atualizado com sucesso!',
            'data' => $cliente
        ]);
    }
}
