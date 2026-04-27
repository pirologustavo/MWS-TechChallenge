<?php

namespace App\Http\Controllers;

use App\Models\Estoque;
use Illuminate\Http\Request;

class EstoqueController extends Controller
{
    public function listar()
    {
        $estoque = Estoque::select('estoqid', 'descricao', 'tipo', 'quantidade_atual', 'quantidade_minima', 'valor_venda')->get();
        return response()->json($estoque);
    }

    public function buscarPorId(string $id)
    {
        $estoque = Estoque::select('estoqid', 'descricao', 'tipo', 'quantidade_atual', 'quantidade_minima', 'valor_venda')->where('estoqid', $id)->first();

        if (!$estoque) {
            return response()->json(['message' => "Item não encontrado!"], 404);
        }

        return response()->json($estoque);
    }

    public function atualizarEstoque(Request $request, string $id)
    {
        $estoque = estoque::where('estoqid', $id)->first();

        if (!$estoque) {
            return response()->json(['message' => "estoque não encontrado"], 404);
        }

        $dados = $request->all();
        $estoque->update($dados);

        return response()->json([
            'message' => 'estoque atualizado com sucesso!',
            'data'    => $estoque
        ]);
    }
}
