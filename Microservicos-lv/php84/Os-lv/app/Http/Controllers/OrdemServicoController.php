<?php

namespace App\Http\Controllers;

use App\Interface\OrdemServicoInterface;
use App\Models\OrdemServico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Interface\OsStatusInterface;

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
                'status_atual' => 'Recebida',
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
                'clientes.clientid',
                'veiculos.carid',
                'veiculos.modelo',
                'veiculos.placa',
                'funcionarios.nome as nome_mecanico',
                'funcionarios.funcid'
            ])
            ->first();

        if (!$os) {
            return response()->json(['message' => 'OS não encontrada'], 404);
        }

        $itens = DB::table('os_itens')
            ->join('estoque', 'os_itens.estoqid', '=', 'estoque.estoqid')
            ->where('osid', $id)
            ->select([
                'estoque.estoqid as id',
                'estoque.descricao',
                'os_itens.quantidade as qtd',
                'os_itens.preco_unitario as preco'
            ])
            ->get();

        return response()->json([
            'os' => $os,
            'itens' => $itens
        ]);
    }

    public function atualizarOs(Request $request, $id)
    {
        $os = OrdemServico::where('osid', $id)->first();

        if (!$os) {
            return response()->json(['message' => "Ordem de Serviço $id não encontrada"], 404);
        }

        $os->update([
            'clientid'     => $request->input('clientid'),
            'carid'        => $request->input('carid'),
            'sintomas'     => $request->input('sintomas'),
            'funcid'       => $request->input('funcid'),
            'valor_total'  => $request->input('valor_total'),
        ]);

        if ($request->has('itens')) {
            $itensEnviados = $request->itens;
            $idsEnviados = [];

            foreach ($itensEnviados as $itemJson) {
                $item = is_array($itemJson) ? $itemJson : json_decode($itemJson, true);
                $estoqid = $item['id'];
                $idsEnviados[] = $estoqid;

                $itemExistente = DB::table('os_itens')
                    ->where('osid', $id)
                    ->where('estoqid', $estoqid)
                    ->first();

                if ($itemExistente) {
                    DB::table('os_itens')
                        ->where('osid', $id)
                        ->where('estoqid', $estoqid)
                        ->update([
                            'quantidade'     => $item['qtd'],
                            'preco_unitario' => $item['preco'],
                            'updated_at'     => now()
                        ]);
                } else {
                    DB::table('os_itens')->insert([
                        'osid'           => $id,
                        'estoqid'        => $estoqid,
                        'quantidade'     => $item['qtd'],
                        'preco_unitario' => $item['preco'],
                        'created_at'     => now(),
                        'updated_at'     => now()
                    ]);
                }
            }

            DB::table('os_itens')
                ->where('osid', $id)
                ->whereNotIn('estoqid', $idsEnviados)
                ->delete();
        }

        return response()->json(['message' => 'Atualizado com sucesso!']);
    }

    public function listarRecebidos()
    {
        $os = OrdemServico::join('clientes', 'clientes.clientid', '=', 'os.clientid')
            ->join('veiculos', 'veiculos.carid', '=', 'os.carid')
            ->join('funcionarios', 'funcionarios.funcid', '=', 'os.funcid')
            ->whereIn('os.statid_atual', [OsStatusInterface::RECEBIDA[1], OsStatusInterface::EM_DIAGNOSTICO[1]])
            ->get(['os.*', 'clientes.nome as nome_cliente', 'veiculos.modelo', 'funcionarios.nome as nome_funcionario']);
        return response()->json($os);
    }

    public function emDiagnostico(string $id)
    {
        $os = OrdemServico::where('osid', $id)
            ->update([
                'status_atual' => OsStatusInterface::EM_DIAGNOSTICO[0],
                'statid_atual' => OsStatusInterface::EM_DIAGNOSTICO[1]
            ]);

        return response()->json([
            'sucesso' => $os > 0,
            'message' => $os > 0 ? 'Status atualizado' : 'OS não encontrada'
        ]);
    }

    public function analiseOs(Request $request, $id)
    {
       # return DB::transaction(function () use ($request, $id) {
            $os = OrdemServico::where('osid', $id)->lockForUpdate()->first();

            if (!$os) {
                return response()->json(['message' => "OS $id não encontrada"], 404);
            }

            $os->update([
                'analise'      => $request->input('analise'),
                'status_atual' => OsStatusInterface::AGUARDANDO_APROVACAO[0],
                'statid_atual' => OsStatusInterface::AGUARDANDO_APROVACAO[1],
                'valor_total'  => $request->input('valor_total')
            ]);

            if ($request->has('itens')) {
                $itensEnviados = $request->itens;
                $idsEnviados = [];

                foreach ($itensEnviados as $itemJson) {
                    $item = is_array($itemJson) ? $itemJson : json_decode($itemJson, true);
                    $estoqid = $item['id'];
                    $idsEnviados[] = $estoqid;

                    DB::table('os_itens')->updateOrInsert(
                        ['osid' => $id, 'estoqid' => $estoqid],
                        [
                            'quantidade'     => $item['qtd'],
                            'preco_unitario' => $item['preco'],
                            'updated_at'     => now(),
                            'created_at'     => DB::raw('IFNULL(created_at, NOW())')
                        ]
                    );
                }

                DB::table('os_itens')
                    ->where('osid', $id)
                    ->whereNotIn('estoqid', $idsEnviados)
                    ->delete();
            }

            return response()->json(['message' => 'Análise finalizada e enviada para aprovação!']);
       # });
    }

    public function listarAguardandoAprovacao()
    {
        $os = OrdemServico::join('clientes', 'clientes.clientid', '=', 'os.clientid')
            ->join('veiculos', 'veiculos.carid', '=', 'os.carid')
            ->join('funcionarios', 'funcionarios.funcid', '=', 'os.funcid')
            ->whereIn('os.statid_atual', [OsStatusInterface::AGUARDANDO_APROVACAO[1]])
            ->get(['os.*', 'clientes.nome as nome_cliente', 'veiculos.modelo', 'funcionarios.nome as nome_funcionario']);
        return response()->json($os);
    }

    public function aprovarOs($id)
    {
        $os = OrdemServico::where('osid', $id)
            ->update([
                'status_atual' => OsStatusInterface::EM_EXECUCAO[0],
                'statid_atual' => OsStatusInterface::EM_EXECUCAO[1]
            ]);

        return response()->json([
            'sucesso' => $os > 0,
            'message' => $os > 0 ? 'Status atualizado' : 'OS não encontrada'
        ]);
    }

    public function finalizarOs(string $id)
    {
        $os = OrdemServico::where('osid', $id)
            ->update([
                'status_atual' => OsStatusInterface::FINALIZADO[0],
                'statid_atual' => OsStatusInterface::FINALIZADO[1]
            ]);

        return response()->json([
            'sucesso' => $os > 0,
            'message' => $os > 0 ? 'Status atualizado' : 'OS não encontrada'
        ]);
    }

    public function entregarOs(string $id)
    {
        $os = OrdemServico::where('osid', $id)
            ->update([
                'status_atual' => OsStatusInterface::ENTREGUE[0],
                'statid_atual' => OsStatusInterface::ENTREGUE[1],
                'entregue' => OrdemServicoInterface::ENTREGUE,
            ]);

        return response()->json([
            'sucesso' => $os > 0,
            'message' => $os > 0 ? 'Status atualizado' : 'OS não encontrada'
        ]);
    }

    public function listarExecucao()
    {
        $os = OrdemServico::join('clientes', 'clientes.clientid', '=', 'os.clientid')
            ->join('veiculos', 'veiculos.carid', '=', 'os.carid')
            ->join('funcionarios', 'funcionarios.funcid', '=', 'os.funcid')
            ->whereIn('os.statid_atual', [OsStatusInterface::EM_EXECUCAO[1], OsStatusInterface::FINALIZADO[1]])
            ->Where('entregue', 0)
            ->get(['os.*', 'clientes.nome as nome_cliente', 'veiculos.modelo', 'funcionarios.nome as nome_funcionario']);
        return response()->json($os);
    }

    public function deletarOs(string $id)
    {
        return DB::transaction(function () use ($id) {
            $os = OrdemServico::where('osid', $id)->first();

            if (!$os) {
                return response()->json(['message' => 'OS não encontrada'], 404);
            }

            $os->itens()->delete();
            $os->historicoStatus()->delete();
            $os->delete();

            return response()->json(['message' => "OS #$id e seus vínculos foram excluídos com sucesso"]);
        });
    }
}
