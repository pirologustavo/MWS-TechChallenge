<?php

namespace App\Http\Controllers;

use App\Models\OrdemServico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrdemServicoController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'clientid'  => 'required|integer',
            'veiculo'   => 'required|integer',
            'tecnico'   => 'required|integer',
            'sintomas'  => 'nullable|string',
            'itens'     => 'required|array|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        return DB::transaction(function () use ($request) {

            $os = OrdemServico::create([
                'clientid'  => $request->clientid,
                'veiculoid' => $request->veiculo,
                'funcid'    => $request->tecnico,
                'sintomas'  => $request->sintomas,
                'status_atual' => 'Aberta'
            ]);

            foreach ($request->itens as $itemJson) {
                $item = is_array($itemJson) ? $itemJson : json_decode($itemJson, true);

                $os->itens()->create([
                    'estoqid'        => $item['id'],
                    'quantidade'     => $item['qtd'],
                    'preco_unitario' => $item['preco']
                ]);
            }

            $os->historicoStatus()->create([
                'statid' => 1
            ]);

            return response()->json([
                'message' => 'Ordem de Serviço criada com sucesso!',
                'osid'    => $os->osid
            ], 201);
        });
    }
}
