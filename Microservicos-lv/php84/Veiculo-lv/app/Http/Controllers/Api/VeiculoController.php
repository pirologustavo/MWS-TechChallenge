<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Veiculo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VeiculoController extends Controller
{
    public function salvar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'modelo'          => 'required|string|max:50',
            'marca'           => 'required|string|max:30',
            'placa'           => 'required|string|regex:/^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/|unique:veiculos',
            'ano'             => 'required|integer|min:1900',
            'clientid' => 'required|exists:clientes,clientid',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $veiculo = Veiculo::create($request->all());

        return response()->json([
            'message' => 'Veículo cadastrado com sucesso!',
            'data'    => $veiculo
        ], 201);
    }

    public function listar()
    {
        $veiculo = Veiculo::join('clientes', 'clientes.clientid', '=', 'veiculos.clientid')->get(['veiculos.*', 'clientes.nome']);
        return response()->json($veiculo);
    }

    public function buscarPorId($id)
    {
        $veiculo = Veiculo::join('clientes', 'clientes.clientid', '=', 'veiculos.clientid')->where('carid', $id)->first();

        if (!$veiculo) {
            return response()->json(['message' => "Veiculo não encontrado!"], 404);
        }

        return response()->json($veiculo);
    }

    public function atualizarVeiculo(Request $request, $id)
    {
        $veiculo = Veiculo::where('carid', $id)->first();

        if (!$veiculo) {
            return response()->json(['message' => "Veiculo não encontrado"], 404);
        }

        $dados = $request->all();
        $veiculo->update($dados);

        return response()->json([
            'message' => 'Veiculo atualizado com sucesso!',
            'data'    => $veiculo
        ]);
    }

    public function buscarVeiculoCliente($id)
    {
        $veiculos = Veiculo::where('clientid', "{$id}")
            ->get(['carid', 'modelo', 'placa']);

        return response()->json($veiculos);
    }
}
