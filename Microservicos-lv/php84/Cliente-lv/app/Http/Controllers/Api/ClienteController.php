<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Services\ClienteAuditTrail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClienteController extends Controller
{
    public function store(Request $request, ClienteAuditTrail $auditTrail)
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
        $auditTrail->record((int) $cliente->clientid, 'created', array_keys($cliente->getAttributes()), $request->attributes->get('correlation_id') ?? $request->header('X-Correlation-ID'));

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

    public function atualizarCliente(Request $request, string $id, ClienteAuditTrail $auditTrail)
    {
        $cliente = Cliente::where('clientid', $id)->first();

        if (!$cliente) {
            return response()->json(['message' => 'Cliente não encontrado!'], 404);
        }

        $dados = $request->all();

        if (isset($dados['cpf'])) {
            $dados['cpf'] = preg_replace("/\D/", "", $dados['cpf']);
        }

        $cliente->fill($dados);
        $changedFields = array_keys($cliente->getDirty());
        $cliente->save();
        if ($changedFields) {
            $auditTrail->record((int) $cliente->clientid, 'updated', $changedFields, $request->attributes->get('correlation_id') ?? $request->header('X-Correlation-ID'));
        }

        return response()->json([
            'message' => 'Cliente atualizado com sucesso!',
            'data' => $cliente
        ]);
    }

    public function deletarCliente(string $id, ClienteAuditTrail $auditTrail, Request $request)
    {
        $cliente = DB::transaction(function () use ($id) {
            $cliente = Cliente::where('clientid', $id)->first();

            if (!$cliente) {
                return null;
            }

            $cliente->delete();

            return $cliente;
        });

        if (!$cliente) return response()->json(['message' => 'Cliente não encontrado'], 404);

        $auditTrail->record((int) $cliente->clientid, 'deleted', ['deleted'], $request->attributes->get('correlation_id') ?? $request->header('X-Correlation-ID'));

        return response()->json(['message' => "Cliente id $id foi deletado com sucesso!"]);
    }

    public function auditoria(string $id, ClienteAuditTrail $auditTrail)
    {
        if (!ctype_digit($id) || (int) $id < 1) {
            return response()->json(['message' => 'ID de cliente inválido.'], 422);
        }

        try {
            return response()->json($auditTrail->history((int) $id));
        } catch (\Throwable $exception) {
            Log::warning('Falha ao consultar auditoria do cliente no MongoDB', [
                'client_id' => (int) $id,
                'exception' => $exception::class,
            ]);
            return response()->json(['message' => 'Histórico de auditoria indisponível.'], 503);
        }
    }
}
