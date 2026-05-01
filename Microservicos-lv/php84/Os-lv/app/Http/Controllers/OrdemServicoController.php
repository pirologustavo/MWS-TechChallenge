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
            'carid'   => 'required|integer',
            'funcid'   => 'required|integer',
            'sintomas'  => 'nullable|string',
            'itens'     => 'required|array|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        return DB::transaction(function () use ($request) {

            $os = OrdemServico::create([
                'clientid'  => $request->clientid,
                'carid'     => $request->carid,
                'funcid'    => $request->funcid,
                'sintomas'  => $request->sintomas,
                'valor_total' => $request->valor_total,
                'status_atual' => 'Aberta',
                'statid_atual' => 1
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

    public function listar()
    {
        $os = OrdemServico::join('clientes', 'clientes.clientid', '=', 'os.clientid')
                            ->join('veiculos', 'veiculos.carid', '=', 'os.carid')
                            ->join('funcionarios', 'funcionarios.funcid', '=', 'os.funcid')
                            ->get(['os.*', 'clientes.nome as nome_cliente', 'veiculos.modelo', 'funcionarios.nome as nome_funcionario']);
        return response()->json($os);
    }

    public function buscarPorId(string $id)
    {
        $os = OrdemServico::join('clientes', 'clientes.clientid', '=', 'os.clientid')
            ->join('veiculos', 'veiculos.carid', '=', 'os.carid')
            ->join('funcionarios', 'funcionarios.funcid', '=', 'os.funcid')
            ->where('os.osid', $id)
            ->select([
                'os.*',
                'clientes.nome as nome_cliente',
                'veiculos.modelo',
                'veiculos.placa',
                'funcionarios.nome as nome_mecanico'
            ])
            ->first();

        if (!$os) {
            return response()->json(['message' => 'Ordem de Serviço não encontrada'], 404);
        }

        return response()->json($os);
    }


}
